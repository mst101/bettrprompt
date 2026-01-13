<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ExportArchivedVisitors extends Command
{
    protected $signature = 'visitors:export-archive
                            {--from= : Start date (YYYY-MM-DD)}
                            {--to= : End date (YYYY-MM-DD)}
                            {--format=csv : Export format: csv or json}
                            {--disk=local : Storage disk: local or s3}';

    protected $description = 'Export archived visitors to CSV or JSON for cold storage';

    public function handle()
    {
        $from = $this->option('from') ? Carbon::parse($this->option('from')) : Carbon::now()->subYear();
        $to = $this->option('to') ? Carbon::parse($this->option('to')) : Carbon::now();
        $format = $this->option('format');
        $disk = $this->option('disk');

        $this->info("Exporting archived visitors from {$from->toDateString()} to {$to->toDateString()}...");
        $this->info("Format: {$format}, Disk: {$disk}");

        $visitors = DB::table('visitors_archive')
            ->whereBetween('archived_at', [$from, $to])
            ->orderBy('archived_at', 'desc')
            ->get();

        if ($visitors->isEmpty()) {
            $this->info('No archived visitors in date range.');

            return 0;
        }

        $this->info("Found {$visitors->count()} archived visitors.");

        // Generate filename
        $filename = sprintf(
            'archived-visitors-%s-to-%s.%s',
            $from->format('Y-m-d'),
            $to->format('Y-m-d'),
            $format
        );

        // Convert to desired format
        $content = $format === 'json'
            ? $this->toJson($visitors)
            : $this->toCsv($visitors);

        // Store file
        try {
            $path = "archives/visitors/{$filename}";
            Storage::disk($disk)->put($path, $content);

            $this->info("✅ Exported to: {$disk}://{$path}");
            $this->line('File size: '.number_format(strlen($content) / 1024, 2).' KB');
            $this->line("Records: {$visitors->count()}");

            // Show archive tier breakdown
            $breakdown = $visitors->groupBy('archive_tier')->map->count();
            if ($breakdown->isNotEmpty()) {
                $this->line("\nArchive tier breakdown:");
                foreach ($breakdown as $tier => $count) {
                    $this->line("  {$tier}: {$count}");
                }
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to export: {$e->getMessage()}");

            return 1;
        }
    }

    protected function toCsv($collection): string
    {
        $csv = '';
        $headers = array_keys((array) $collection->first());
        $csv .= implode(',', array_map([$this, 'escapeCsv'], $headers))."\n";

        foreach ($collection as $row) {
            $values = array_map([$this, 'escapeCsv'], (array) $row);
            $csv .= implode(',', $values)."\n";
        }

        return $csv;
    }

    protected function escapeCsv($value): string
    {
        return '"'.str_replace('"', '""', (string) $value).'"';
    }

    protected function toJson($collection): string
    {
        return json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
