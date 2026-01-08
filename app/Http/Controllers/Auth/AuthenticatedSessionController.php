<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SetLocale;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\VisitorMigrationService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Migrate visitor data to logged-in user
        $visitorId = $request->cookie('visitor_id');
        $claimedCount = 0;

        if ($visitorId) {
            $user = Auth::user();
            $migrationService = new VisitorMigrationService;
            $claimedCount = $migrationService->migrateVisitorToUser($user, $visitorId);
        }

        $locale = SetLocale::detectLocale($request);

        // If visitor had completed prompts, redirect to history page
        if ($claimedCount > 0) {
            return redirect()->intended(route('prompt-builder.history', ['locale' => $locale], absolute: false));
        }

        return redirect()->intended(route('prompt-builder.index', ['locale' => $locale], absolute: false));
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

            return redirect('/')->with('status', 'You have been logged out successfully.');

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

            return redirect('/')->with('status', 'You have been logged out.');
        }
    }
}
