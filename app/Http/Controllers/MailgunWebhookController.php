<?php

namespace App\Http\Controllers;

use App\Services\MailgunEventService;
use App\Services\MailgunInboundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MailgunWebhookController extends Controller
{
    public function __construct(
        private MailgunEventService $eventService,
        private MailgunInboundService $inboundService
    ) {}

    /**
     * Handle Mailgun event webhooks (delivered, opened, clicked, bounced, etc.)
     *
     * @see https://documentation.mailgun.com/en/latest/api-events.html
     */
    public function handleEvent(Request $request): JsonResponse
    {
        try {
            Log::info('Mailgun event webhook received', [
                'event_type' => $request->input('event-data.event'),
                'message_id' => $request->input('event-data.message.headers.message-id'),
            ]);

            $this->eventService->processEvent($request->all());

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Failed to process Mailgun event webhook', [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to process event',
            ], 500);
        }
    }

    /**
     * Handle Mailgun inbound email webhooks
     *
     * @see https://documentation.mailgun.com/en/latest/api-receiving.html
     */
    public function handleInbound(Request $request): JsonResponse
    {
        try {
            Log::info('Mailgun inbound webhook received', [
                'from' => $request->input('sender'),
                'to' => $request->input('recipient'),
                'subject' => $request->input('subject'),
            ]);

            $this->inboundService->processInbound($request->all());

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Failed to process Mailgun inbound webhook', [
                'error' => $e->getMessage(),
                'from' => $request->input('sender'),
                'to' => $request->input('recipient'),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to process inbound email',
            ], 500);
        }
    }
}
