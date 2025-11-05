<?php

namespace App\Console\Commands;

use App\Services\CloudflareAIService;
use Illuminate\Console\Command;

class TestCloudflareAI extends Command
{
    protected $signature = 'ai:test {message?}';

    protected $description = 'Test the Cloudflare AI integration';

    public function handle()
    {
        $this->info('Testing Cloudflare AI integration...');

        try {
            $aiService = new CloudflareAIService;

            // Test connection first
            $this->info('Testing connection...');
            $connectionTest = $aiService->testConnection();

            $this->info('Connection test result:');
            $this->line('Status: '.$connectionTest['status']);
            $this->line('Success: '.($connectionTest['success'] ? 'true' : 'false'));
            $this->line('Response: '.$connectionTest['response']);

            if (! $connectionTest['success']) {
                $this->error('Connection failed');

                return 1;
            }

            $this->info('✓ Connection successful');

            // Test customer service response
            $message = $this->argument('message') ?? 'Hello, I need help booking a trip to The Gambia';
            $this->info("Testing customer service response for: '{$message}'");

            $response = $aiService->generateCustomerServiceResponse($message);

            $this->info('AI Response:');
            $this->line('---');
            $this->line($response);
            $this->line('---');

            $this->info('✓ Test completed successfully');

            return 0;

        } catch (\Exception $e) {
            $this->error('Test failed: '.$e->getMessage());

            return 1;
        }
    }
}
