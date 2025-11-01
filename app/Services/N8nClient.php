<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class N8nClient
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;

    public function __construct()
    {
        $this->baseUrl = config('services.n8n.url');
        $this->username = config('services.n8n.username');
        $this->password = config('services.n8n.password');
    }

    public function triggerWebhook(string $path, array $payload = [])
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->post(rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/'), $payload);

            if ($response->failed()) {
                Log::error('n8n webhook failed', [
                    'path' => $path,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }

            return $response;
        } catch (\Throwable $e) {
            Log::error('n8n call error: ' . $e->getMessage(), [
                'path' => $path,
                'payload' => $payload,
            ]);
            throw $e;
        }
    }
}
