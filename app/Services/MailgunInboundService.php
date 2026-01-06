<?php

namespace App\Services;

use App\Models\InboundEmail;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class MailgunInboundService
{
    /**
     * Process a Mailgun inbound email webhook
     *
     * @see https://documentation.mailgun.com/en/latest/api-receiving.html
     */
    public function processInbound(array $payload): void
    {
        $messageId = $payload['Message-Id'] ?? null;
        $from = $payload['sender'] ?? $payload['From'] ?? null;
        $to = $payload['recipient'] ?? $payload['To'] ?? null;
        $subject = $payload['subject'] ?? $payload['Subject'] ?? null;
        $bodyPlain = $payload['body-plain'] ?? $payload['stripped-text'] ?? null;
        $bodyHtml = $payload['body-html'] ?? $payload['stripped-html'] ?? null;
        $strippedText = $payload['stripped-text'] ?? null;
        $strippedSignature = $payload['stripped-signature'] ?? null;
        $timestamp = $payload['timestamp'] ?? null;

        if (! $messageId || ! $from || ! $to) {
            Log::warning('Mailgun inbound email missing required fields', [
                'has_message_id' => ! empty($messageId),
                'has_from' => ! empty($from),
                'has_to' => ! empty($to),
            ]);

            return;
        }

        // Check if we've already processed this email (prevent duplicates)
        if (InboundEmail::where('message_id', $messageId)->exists()) {
            Log::info('Mailgun inbound email already processed', [
                'message_id' => $messageId,
            ]);

            return;
        }

        // Extract headers
        $headers = $this->extractHeaders($payload);

        // Extract attachments metadata
        $attachments = $this->extractAttachments($payload);

        // Try to match email to a user
        $user = $this->matchUser($from, $to);

        // Store inbound email
        try {
            InboundEmail::create([
                'message_id' => $messageId,
                'from' => $from,
                'to' => $to,
                'subject' => $subject,
                'body_plain' => $bodyPlain,
                'body_html' => $bodyHtml,
                'stripped_text' => $strippedText,
                'stripped_signature' => $strippedSignature,
                'headers' => $headers,
                'attachments' => $attachments,
                'user_id' => $user?->id,
                'received_at' => $timestamp ? date('Y-m-d H:i:s', $timestamp) : now(),
                'processed_at' => now(),
            ]);

            Log::info('Mailgun inbound email processed successfully', [
                'message_id' => $messageId,
                'from' => $from,
                'to' => $to,
                'subject' => $subject,
                'user_id' => $user?->id,
            ]);

            // TODO: Implement CRM logic
            // - Parse email for thread context (using plus-addressing or References header)
            // - Store as CRM activity
            // - Trigger notifications if needed
        } catch (\Exception $e) {
            Log::error('Failed to store Mailgun inbound email', [
                'error' => $e->getMessage(),
                'message_id' => $messageId,
                'from' => $from,
            ]);

            throw $e;
        }
    }

    /**
     * Extract email headers from payload
     */
    private function extractHeaders(array $payload): array
    {
        $headers = [];

        // Common headers to extract
        $headerKeys = [
            'In-Reply-To',
            'References',
            'Date',
            'Received',
            'Return-Path',
            'X-Mailgun-*',
        ];

        foreach ($payload as $key => $value) {
            // Check if key matches any header pattern
            foreach ($headerKeys as $headerKey) {
                if (str_starts_with($key, str_replace('*', '', $headerKey))) {
                    $headers[$key] = $value;
                }
            }
        }

        return $headers;
    }

    /**
     * Extract attachment metadata from payload
     */
    private function extractAttachments(array $payload): array
    {
        $attachments = [];
        $attachmentCount = (int) ($payload['attachment-count'] ?? 0);

        for ($i = 1; $i <= $attachmentCount; $i++) {
            $attachmentKey = "attachment-{$i}";

            if (isset($payload[$attachmentKey])) {
                $attachments[] = [
                    'name' => $payload[$attachmentKey]['name'] ?? "attachment-{$i}",
                    'content_type' => $payload[$attachmentKey]['content-type'] ?? 'application/octet-stream',
                    'size' => $payload[$attachmentKey]['size'] ?? null,
                ];
            }
        }

        return $attachments;
    }

    /**
     * Try to match the email to a user in the system
     */
    private function matchUser(string $from, string $to): ?User
    {
        // First, try to match by sender email
        $user = User::where('email', $from)->first();

        if ($user) {
            return $user;
        }

        // If using plus-addressing (e.g., user+123@mg.bettrprompt.ai),
        // extract the user ID and look them up
        if (preg_match('/\+(\d+)@/', $to, $matches)) {
            $userId = (int) $matches[1];
            $user = User::find($userId);

            if ($user) {
                return $user;
            }
        }

        return null;
    }
}
