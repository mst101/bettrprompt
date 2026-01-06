<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyMailgunSignature
{
    /**
     * Handle an incoming request.
     *
     * Verifies Mailgun webhook signatures according to:
     * https://documentation.mailgun.com/en/latest/user_manual.html#securing-webhooks
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $signingKey = config('services.mailgun.webhook_signing_key');

        // Skip verification if no signing key is configured (for local development)
        if (empty($signingKey)) {
            Log::warning('Mailgun webhook signature verification skipped - no signing key configured');

            return $next($request);
        }

        // Extract signature data from request
        $timestamp = $request->input('signature.timestamp');
        $token = $request->input('signature.token');
        $signature = $request->input('signature.signature');

        // Verify all required signature components are present
        if (! $timestamp || ! $token || ! $signature) {
            Log::warning('Mailgun webhook rejected - missing signature data', [
                'has_timestamp' => ! empty($timestamp),
                'has_token' => ! empty($token),
                'has_signature' => ! empty($signature),
            ]);

            return response()->json([
                'error' => 'Invalid signature',
            ], 401);
        }

        // Verify timestamp is recent (prevent replay attacks)
        // Allow 5 minutes of clock drift
        if (abs(time() - $timestamp) > 300) {
            Log::warning('Mailgun webhook rejected - timestamp too old', [
                'timestamp' => $timestamp,
                'current_time' => time(),
                'diff' => abs(time() - $timestamp),
            ]);

            return response()->json([
                'error' => 'Invalid signature - timestamp too old',
            ], 401);
        }

        // Compute expected signature
        $expectedSignature = hash_hmac('sha256', $timestamp.$token, $signingKey);

        // Verify signature matches
        if (! hash_equals($expectedSignature, $signature)) {
            Log::warning('Mailgun webhook rejected - invalid signature', [
                'timestamp' => $timestamp,
            ]);

            return response()->json([
                'error' => 'Invalid signature',
            ], 401);
        }

        return $next($request);
    }
}
