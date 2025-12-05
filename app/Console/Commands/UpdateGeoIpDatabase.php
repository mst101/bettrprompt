<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateGeoIpDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geoip:update {--force : Force download even if database exists}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Download or update the MaxMind GeoLite2 City database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🌍 MaxMind GeoIP Database Update');
        $this->line('');

        // Check if geolocation is enabled
        if (! config('geoip.enabled')) {
            $this->warn('⚠️  Geolocation is disabled in config/geoip.php');

            return self::SUCCESS;
        }

        // Get credentials from environment
        $accountId = config('geoip.maxmind.account_id');
        $licenseKey = config('geoip.maxmind.license_key');

        if (! $accountId || ! $licenseKey) {
            $this->error('❌ Error: MaxMind credentials not configured');
            $this->line('Please set MAXMIND_ACCOUNT_ID and MAXMIND_LICENSE_KEY in your .env file');

            return self::FAILURE;
        }

        // Get database path
        $databasePath = config('geoip.maxmind.database_path');
        $databaseDir = dirname($databasePath);

        // Create directory if it doesn't exist
        if (! is_dir($databaseDir)) {
            if (! mkdir($databaseDir, 0755, true)) {
                $this->error("❌ Failed to create directory: {$databaseDir}");

                return self::FAILURE;
            }
            $this->info("✓ Created directory: {$databaseDir}");
        }

        // Check if database exists and if we should skip download
        if (file_exists($databasePath) && ! $this->option('force')) {
            $fileAge = time() - filemtime($databasePath);
            $daysSinceUpdate = floor($fileAge / 86400);
            $this->info('✓ Database already exists');
            $this->line("  Last updated: {$daysSinceUpdate} day(s) ago");
            $this->line('  Use --force flag to re-download');

            return self::SUCCESS;
        }

        // Download the database
        $this->info('📥 Downloading GeoLite2 City database...');

        try {
            $url = $this->buildDownloadUrl($accountId, $licenseKey);
            $tempFile = $databaseDir.'/GeoLite2-City.tar.gz';

            // Download file
            if (! $this->downloadFile($url, $tempFile)) {
                $this->error('❌ Failed to download database');

                return self::FAILURE;
            }

            // Extract tar.gz
            $this->info('📦 Extracting database...');
            if (! $this->extractTarGz($tempFile, $databaseDir)) {
                $this->error('❌ Failed to extract database');
                if (file_exists($tempFile)) {
                    unlink($tempFile);
                }

                return self::FAILURE;
            }

            // Remove tar file
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }

            // Find and move the MMDB file (it might be in a subdirectory)
            if (! file_exists($databasePath)) {
                $mmdbFile = $this->findMmdbFile($databaseDir);
                if ($mmdbFile && $mmdbFile !== $databasePath) {
                    if (rename($mmdbFile, $databasePath)) {
                        // Clean up empty directory
                        $parentDir = dirname($mmdbFile);
                        if (is_dir($parentDir) && $parentDir !== $databaseDir) {
                            @rmdir($parentDir);
                        }
                    } else {
                        $this->error('❌ Failed to move database file from subdirectory');

                        return self::FAILURE;
                    }
                } else {
                    $this->error('❌ Database file not found after extraction');

                    return self::FAILURE;
                }
            }

            // Set permissions
            chmod($databasePath, 0644);

            $this->info('✓ Database downloaded and extracted successfully');
            $this->line("  Location: {$databasePath}");
            $this->line('  Size: '.$this->formatBytes(filesize($databasePath)));

            Log::info('GeoIP database updated successfully', [
                'path' => $databasePath,
                'size' => filesize($databasePath),
            ]);

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error during download/extraction: '.$e->getMessage());
            Log::error('Failed to update GeoIP database', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }

    /**
     * Build the MaxMind download URL
     */
    private function buildDownloadUrl(string $accountId, string $licenseKey): string
    {
        return sprintf(
            'https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-City&license_key=%s&suffix=tar.gz',
            urlencode($licenseKey)
        );
    }

    /**
     * Download file from URL
     */
    private function downloadFile(string $url, string $destination): bool
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5 minute timeout
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($httpCode !== 200) {
            $this->error("HTTP Error {$httpCode}");
            if ($httpCode === 401) {
                $this->error('Authentication failed - check your MaxMind credentials');
            }

            return false;
        }

        if ($error) {
            $this->error("cURL Error: {$error}");

            return false;
        }

        if ($content === false || empty($content)) {
            $this->error('Failed to download file or received empty response');

            return false;
        }

        if (file_put_contents($destination, $content) === false) {
            $this->error("Failed to write file to: {$destination}");

            return false;
        }

        return true;
    }

    /**
     * Extract tar.gz file
     */
    private function extractTarGz(string $source, string $destination): bool
    {
        try {
            // Try using PHP's PharData
            if (class_exists('\PharData')) {
                $phar = new \PharData($source);
                $phar->extractTo($destination, null, true);

                return true;
            }

            // Fallback to tar command
            $command = sprintf(
                'cd %s && tar -xzf %s',
                escapeshellarg($destination),
                escapeshellarg($source)
            );

            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);

            return $returnCode === 0;
        } catch (\Exception $e) {
            $this->error('Extraction error: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Find GeoLite2-City.mmdb file in directory or subdirectories
     */
    private function findMmdbFile(string $directory): ?string
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file->isFile() && $file->getFilename() === 'GeoLite2-City.mmdb') {
                return $file->getPathname();
            }
        }

        return null;
    }

    /**
     * Format bytes to human-readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2).' '.$units[$pow];
    }
}
