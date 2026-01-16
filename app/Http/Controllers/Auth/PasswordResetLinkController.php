<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Models\AnalyticsEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(ForgotPasswordRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink([
            'email' => $validated['email'],
        ]);

        if ($status == Password::RESET_LINK_SENT) {
            // Track password reset request
            $context = $this->getAnalyticsContext($request);
            AnalyticsEvent::create([
                'event_id' => (string) Str::uuid(),
                'name' => 'password_reset_requested',
                'visitor_id' => getVisitorIdFromCookie($request),
                'source' => 'server',
                'occurred_at' => now(),
                'session_id' => $context['session_id'],
                'page_path' => $context['page_path'],
                'referrer' => $context['referrer'],
                'device_type' => $context['device_type'],
                'properties' => [
                    'email' => $validated['email'],
                ],
            ]);

            return back()->with('status', __($status));
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }
}
