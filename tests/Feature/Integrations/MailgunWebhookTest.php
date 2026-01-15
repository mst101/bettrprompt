<?php

use App\Models\EmailEvent;
use App\Models\InboundEmail;
use App\Models\User;

beforeEach(function () {
    // Set up a valid webhook signing key
    $this->validSigningKey = 'test-mailgun-signing-key-123';
    config(['services.mailgun.webhook_signing_key' => $this->validSigningKey]);
});

describe('Mailgun signature verification', function () {
    test('webhook requires valid signature', function () {
        $timestamp = time();
        $token = 'test-token-123';
        $signature = hash_hmac('sha256', $timestamp.$token, $this->validSigningKey);

        $response = mailgunEventPost([
            'signature' => [
                'timestamp' => $timestamp,
                'token' => $token,
                'signature' => $signature,
            ],
            'event-data' => [
                'event' => 'delivered',
                'message' => [
                    'headers' => [
                        'message-id' => 'test-message-id@mg.bettrprompt.ai',
                    ],
                ],
                'recipient' => 'test@example.com',
                'timestamp' => $timestamp,
            ],
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    });

    test('webhook rejects missing signature', function () {
        $response = mailgunEventPost([
            'event-data' => [
                'event' => 'delivered',
                'message' => [
                    'headers' => [
                        'message-id' => 'test-message-id@mg.bettrprompt.ai',
                    ],
                ],
                'recipient' => 'test@example.com',
            ],
        ]);

        $response->assertStatus(401);
        $response->assertJson(['error' => 'Invalid signature']);
    });

    test('webhook rejects invalid signature', function () {
        $timestamp = time();
        $token = 'test-token-123';

        $response = mailgunEventPost([
            'signature' => [
                'timestamp' => $timestamp,
                'token' => $token,
                'signature' => 'invalid-signature',
            ],
            'event-data' => [
                'event' => 'delivered',
                'message' => [
                    'headers' => [
                        'message-id' => 'test-message-id@mg.bettrprompt.ai',
                    ],
                ],
                'recipient' => 'test@example.com',
            ],
        ]);

        $response->assertStatus(401);
        $response->assertJson(['error' => 'Invalid signature']);
    });

    test('webhook rejects old timestamp', function () {
        $timestamp = time() - 600; // 10 minutes ago
        $token = 'test-token-123';
        $signature = hash_hmac('sha256', $timestamp.$token, $this->validSigningKey);

        $response = mailgunEventPost([
            'signature' => [
                'timestamp' => $timestamp,
                'token' => $token,
                'signature' => $signature,
            ],
            'event-data' => [
                'event' => 'delivered',
                'message' => [
                    'headers' => [
                        'message-id' => 'test-message-id@mg.bettrprompt.ai',
                    ],
                ],
                'recipient' => 'test@example.com',
            ],
        ]);

        $response->assertStatus(401);
        $response->assertJson(['error' => 'Invalid signature - timestamp too old']);
    });

    test('webhook allows requests when no signing key configured', function () {
        config(['services.mailgun.webhook_signing_key' => null]);

        $response = mailgunEventPost([
            'event-data' => [
                'event' => 'delivered',
                'message' => [
                    'headers' => [
                        'message-id' => 'test-message-id@mg.bettrprompt.ai',
                    ],
                ],
                'recipient' => 'test@example.com',
                'timestamp' => time(),
            ],
        ]);

        $response->assertOk();
    });
});

describe('Mailgun event webhooks', function () {
    test('processes delivered event', function () {
        $user = User::factory()->create(['email' => 'test@example.com']);
        $timestamp = time();
        $messageId = 'test-message-id@mg.bettrprompt.ai';

        $response = mailgunEventPostWithSignature([
            'event-data' => [
                'event' => 'delivered',
                'message' => [
                    'headers' => [
                        'message-id' => $messageId,
                    ],
                ],
                'recipient' => $user->email,
                'timestamp' => $timestamp,
            ],
        ]);

        $response->assertOk();

        expect(EmailEvent::count())->toBe(1);
        $event = EmailEvent::first();
        expect($event->event_type)->toBe('delivered');
        expect($event->message_id)->toBe($messageId);
        expect($event->recipient)->toBe($user->email);
        expect($event->user_id)->toBe($user->id);
    });

    test('processes opened event', function () {
        $user = User::factory()->create(['email' => 'test@example.com']);
        $timestamp = time();
        $messageId = 'test-message-id@mg.bettrprompt.ai';

        $response = mailgunEventPostWithSignature([
            'event-data' => [
                'event' => 'opened',
                'message' => [
                    'headers' => [
                        'message-id' => $messageId,
                    ],
                ],
                'recipient' => $user->email,
                'timestamp' => $timestamp,
            ],
        ]);

        $response->assertOk();

        expect(EmailEvent::count())->toBe(1);
        $event = EmailEvent::first();
        expect($event->event_type)->toBe('opened');
    });

    test('processes clicked event', function () {
        $user = User::factory()->create(['email' => 'test@example.com']);
        $timestamp = time();
        $messageId = 'test-message-id@mg.bettrprompt.ai';

        $response = mailgunEventPostWithSignature([
            'event-data' => [
                'event' => 'clicked',
                'message' => [
                    'headers' => [
                        'message-id' => $messageId,
                    ],
                ],
                'recipient' => $user->email,
                'timestamp' => $timestamp,
                'url' => 'https://example.com/link',
            ],
        ]);

        $response->assertOk();

        expect(EmailEvent::count())->toBe(1);
        $event = EmailEvent::first();
        expect($event->event_type)->toBe('clicked');
    });

    test('processes bounced event', function () {
        $user = User::factory()->create(['email' => 'test@example.com']);
        $timestamp = time();
        $messageId = 'test-message-id@mg.bettrprompt.ai';

        $response = mailgunEventPostWithSignature([
            'event-data' => [
                'event' => 'bounced',
                'message' => [
                    'headers' => [
                        'message-id' => $messageId,
                    ],
                ],
                'recipient' => $user->email,
                'timestamp' => $timestamp,
                'reason' => 'Mailbox does not exist',
            ],
        ]);

        $response->assertOk();

        expect(EmailEvent::count())->toBe(1);
        $event = EmailEvent::first();
        expect($event->event_type)->toBe('bounced');
    });

    test('stores full payload in database', function () {
        $user = User::factory()->create(['email' => 'test@example.com']);
        $timestamp = time();
        $messageId = 'test-message-id@mg.bettrprompt.ai';

        $payload = [
            'event-data' => [
                'event' => 'delivered',
                'message' => [
                    'headers' => [
                        'message-id' => $messageId,
                    ],
                ],
                'recipient' => $user->email,
                'timestamp' => $timestamp,
                'custom-field' => 'custom-value',
            ],
        ];

        $response = mailgunEventPostWithSignature($payload);

        $response->assertOk();

        $event = EmailEvent::first();
        expect($event->payload)->toBeArray();
        expect($event->payload['event-data']['custom-field'])->toBe('custom-value');
    });

    test('prevents duplicate events', function () {
        $user = User::factory()->create(['email' => 'test@example.com']);
        $timestamp = time();
        $messageId = 'test-message-id@mg.bettrprompt.ai';

        $payload = [
            'event-data' => [
                'event' => 'delivered',
                'message' => [
                    'headers' => [
                        'message-id' => $messageId,
                    ],
                ],
                'recipient' => $user->email,
                'timestamp' => $timestamp,
            ],
        ];

        // Send same payload twice
        mailgunEventPostWithSignature($payload);
        mailgunEventPostWithSignature($payload);

        // Should only create one event due to unique constraint
        expect(EmailEvent::count())->toBe(1);
    });
});

describe('Mailgun inbound webhooks', function () {
    test('processes inbound email', function () {
        $user = User::factory()->create(['email' => 'sender@example.com']);
        $timestamp = time();
        $messageId = 'incoming-message-id@mg.bettrprompt.ai';

        $response = mailgunInboundPostWithSignature([
            'Message-Id' => $messageId,
            'sender' => $user->email,
            'recipient' => 'hello@mg.bettrprompt.ai',
            'subject' => 'Test Email Subject',
            'body-plain' => 'This is the plain text body.',
            'body-html' => '<p>This is the HTML body.</p>',
            'stripped-text' => 'This is the stripped text.',
            'stripped-signature' => 'Email signature',
            'timestamp' => $timestamp,
        ]);

        $response->assertOk();

        expect(InboundEmail::count())->toBe(1);
        $email = InboundEmail::first();
        expect($email->message_id)->toBe($messageId);
        expect($email->from)->toBe($user->email);
        expect($email->to)->toBe('hello@mg.bettrprompt.ai');
        expect($email->subject)->toBe('Test Email Subject');
        expect($email->body_plain)->toBe('This is the plain text body.');
        expect($email->user_id)->toBe($user->id);
    });

    test('matches user by sender email', function () {
        $user = User::factory()->create(['email' => 'sender@example.com']);
        $timestamp = time();
        $messageId = 'incoming-message-id@mg.bettrprompt.ai';

        mailgunInboundPostWithSignature([
            'Message-Id' => $messageId,
            'sender' => $user->email,
            'recipient' => 'hello@mg.bettrprompt.ai',
            'subject' => 'Test',
            'timestamp' => $timestamp,
        ]);

        $email = InboundEmail::first();
        expect($email->user_id)->toBe($user->id);
    });

    test('matches user by plus-addressing', function () {
        $user = User::factory()->create();
        $timestamp = time();
        $messageId = 'incoming-message-id@mg.bettrprompt.ai';

        mailgunInboundPostWithSignature([
            'Message-Id' => $messageId,
            'sender' => 'external@example.com',
            'recipient' => "reply+{$user->id}@mg.bettrprompt.ai",
            'subject' => 'Test',
            'timestamp' => $timestamp,
        ]);

        $email = InboundEmail::first();
        expect($email->user_id)->toBe($user->id);
    });

    test('prevents duplicate inbound emails', function () {
        $timestamp = time();
        $messageId = 'incoming-message-id@mg.bettrprompt.ai';

        $payload = [
            'Message-Id' => $messageId,
            'sender' => 'sender@example.com',
            'recipient' => 'hello@mg.bettrprompt.ai',
            'subject' => 'Test',
            'timestamp' => $timestamp,
        ];

        // Send same payload twice
        mailgunInboundPostWithSignature($payload);
        mailgunInboundPostWithSignature($payload);

        // Should only create one email due to duplicate check
        expect(InboundEmail::count())->toBe(1);
    });
});

/*
|--------------------------------------------------------------------------
| Mailgun Testing Helpers
|--------------------------------------------------------------------------
*/

/** Helper function to make Mailgun event webhook requests */
function mailgunEventPost(array $data): \Illuminate\Testing\TestResponse
{
    return test()->postJson('/api/webhooks/mailgun/events', $data);
}

/** Helper function to make Mailgun event webhook requests with valid signature */
function mailgunEventPostWithSignature(array $data): \Illuminate\Testing\TestResponse
{
    $timestamp = $data['signature']['timestamp'] ?? time();
    $token = $data['signature']['token'] ?? 'test-token-'.uniqid();
    $signingKey = test()->validSigningKey;
    $signature = hash_hmac('sha256', $timestamp.$token, $signingKey);

    $data['signature'] = [
        'timestamp' => $timestamp,
        'token' => $token,
        'signature' => $signature,
    ];

    return mailgunEventPost($data);
}

/** Helper function to make Mailgun inbound webhook requests */
function mailgunInboundPost(array $data): \Illuminate\Testing\TestResponse
{
    return test()->postJson('/api/webhooks/mailgun/inbound', $data);
}

/** Helper function to make Mailgun inbound webhook requests with valid signature */
function mailgunInboundPostWithSignature(array $data): \Illuminate\Testing\TestResponse
{
    $timestamp = $data['signature']['timestamp'] ?? time();
    $token = $data['signature']['token'] ?? 'test-token-'.uniqid();
    $signingKey = test()->validSigningKey;
    $signature = hash_hmac('sha256', $timestamp.$token, $signingKey);

    $data['signature'] = [
        'timestamp' => $timestamp,
        'token' => $token,
        'signature' => $signature,
    ];

    return mailgunInboundPost($data);
}
