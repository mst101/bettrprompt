<?php

namespace App\Services;

use App\Models\EmailEvent;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class MailgunEventService
{
    /**
     * Process a Mailgun event webhook
     *
     * @see https://documentation.mailgun.com/en/latest/api-events.html
     */
    public function processEvent(array $payload): void
    {
        $eventData = $payload['event-data'] ?? [];
        $eventType = $eventData['event'] ?? null;
        $messageId = $eventData['message']['headers']['message-id'] ?? null;
        $recipient = $eventData['recipient'] ?? null;
        $timestamp = $eventData['timestamp'] ?? null;

        if (! $eventType || ! $messageId || ! $recipient) {
            Log::warning('Mailgun event missing required fields', [
                'has_event_type' => ! empty($eventType),
                'has_message_id' => ! empty($messageId),
                'has_recipient' => ! empty($recipient),
            ]);

            return;
        }

        // Find user by email
        $user = User::where('email', $recipient)->first();

        // Store event in database
        try {
            EmailEvent::updateOrCreate(
                [
                    'message_id' => $messageId,
                    'event_type' => $eventType,
                    'event_timestamp' => $timestamp ? date('Y-m-d H:i:s', $timestamp) : null,
                ],
                [
                    'recipient' => $recipient,
                    'user_id' => $user?->id,
                    'payload' => $payload,
                    'processed_at' => now(),
                ]
            );

            Log::info('Mailgun event processed successfully', [
                'event_type' => $eventType,
                'message_id' => $messageId,
                'recipient' => $recipient,
            ]);

            // Handle specific event types
            $this->handleEventType($eventType, $eventData, $user);
        } catch (\Exception $e) {
            Log::error('Failed to store Mailgun event', [
                'error' => $e->getMessage(),
                'event_type' => $eventType,
                'message_id' => $messageId,
            ]);

            throw $e;
        }
    }

    /**
     * Handle specific event types (bounces, complaints, unsubscribes)
     */
    private function handleEventType(string $eventType, array $eventData, ?User $user): void
    {
        if (! $user) {
            return;
        }

        // Handle bounces - mark email as invalid
        if ($eventType === 'bounced' || $eventType === 'failed') {
            Log::info('Email bounced - consider marking as invalid', [
                'user_id' => $user->id,
                'email' => $user->email,
                'reason' => $eventData['reason'] ?? null,
            ]);

            // TODO: Implement bounce handling logic
            // - Hard bounces: Mark email as invalid, suspend email sending
            // - Soft bounces: Track and retry with backoff
        }

        // Handle complaints (spam reports)
        if ($eventType === 'complained') {
            Log::warning('User reported email as spam', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            // TODO: Implement complaint handling logic
            // - Immediately suppress this email address
            // - Review sending practices
        }

        // Handle unsubscribes
        if ($eventType === 'unsubscribed') {
            Log::info('User unsubscribed from emails', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            // TODO: Implement unsubscribe handling logic
            // - Update user preferences
            // - Suppress from marketing emails
        }
    }
}
