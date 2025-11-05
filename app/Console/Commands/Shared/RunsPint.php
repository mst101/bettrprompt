<?php

namespace App\Console\Commands\Shared;

trait RunsPint
{
    /**
     * Run Laravel Pint on the given file.
     */
    protected function runPintOnFile(string $filePath): void
    {
        $isInSail = getenv('SAIL') || file_exists('/.dockerenv');
        $command = $isInSail
            ? [base_path('vendor/bin/pint'), $filePath]
            : [base_path('vendor/bin/sail'), 'pint', $filePath];
        $tmpDir = base_path('storage/framework/tmp');
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }
        putenv('TMPDIR='.$tmpDir); // Set TMPDIR globally as well
        $env = array_merge($_ENV, ['TMPDIR' => $tmpDir]);
        $process = new \Symfony\Component\Process\Process($command, null, $env);
        $process->setTimeout(60);
        $process->run();
        if (! $process->isSuccessful()) {
            $this->warn('Pint error: '.$process->getErrorOutput());
            $this->warn('Pint output: '.$process->getOutput());
        }
    }
}
