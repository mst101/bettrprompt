<?php

namespace App\Http\Middleware;

use App\Services\EncryptionService;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class RequirePrivacyUnlock
{
    public function __construct(
        private EncryptionService $encryptionService,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Skip if not authenticated or no privacy enabled
        if (! $user || ! $user->hasPrivacyEnabled()) {
            return $next($request);
        }

        // Check if DEK is in session
        if ($this->encryptionService->hasDekInSession()) {
            return $next($request);
        }

        // Privacy user without unlocked key - redirect to unlock
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'privacy_locked',
                'message' => 'Please unlock your privacy key to continue.',
                'unlock_url' => route('privacy.unlock'),
            ], 403);
        }

        // Store intended URL and redirect to unlock
        session(['url.intended' => $request->url()]);

        return Inertia::render('Settings/PrivacyUnlock', [
            'message' => 'Please enter your password to unlock your encrypted data.',
        ])->toResponse($request);
    }
}
