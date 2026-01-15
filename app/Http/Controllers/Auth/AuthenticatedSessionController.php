<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SetCountry;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\AnalyticsEvent;
use App\Services\VisitorMigrationService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Track login completion
        $context = $this->getAnalyticsContext($request);
        AnalyticsEvent::create([
            'event_id' => (string) Str::uuid(),
            'name' => 'login_completed',
            'visitor_id' => $request->cookie('visitor_id'),
            'user_id' => Auth::id(),
            'source' => 'server',
            'occurred_at' => now(),
            'session_id' => $context['session_id'],
            'page_path' => $context['page_path'],
            'referrer' => $context['referrer'],
            'device_type' => $context['device_type'],
            'properties' => [
                'login_method' => 'email',
            ],
        ]);

        // Migrate visitor data to logged-in user
        $visitorId = $request->cookie('visitor_id');
        $claimedCount = 0;

        if ($visitorId) {
            $user = Auth::user();
            $migrationService = new VisitorMigrationService;
            $claimedCount = $migrationService->migrateVisitorToUser($user, $visitorId);
        }

        $country = SetCountry::detectCountry($request);

        // If visitor had completed prompts, redirect to history page
        if ($claimedCount > 0) {
            return redirect()->intended(countryRoute('prompt-builder.history', [], false));
        }

        return redirect()->intended(countryRoute('prompt-builder.index', [], false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        try {
            Auth::guard('web')->logout();

            $request->session()->invalidate();

            $request->session()->regenerateToken();

            return redirect('/')->with('status', __('messages.auth.logged_out'));

        } catch (Exception $e) {
            Log::error('Logout failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            // Even if logout fails, clear session and redirect
            // This handles edge cases where session is already invalid
            try {
                $request->session()->flush();
                $request->session()->regenerateToken();
            } catch (Exception $sessionError) {
                // Ignore session errors at this point
            }

            return redirect('/')->with('status', __('messages.auth.logged_out_session'));
        }
    }
}
