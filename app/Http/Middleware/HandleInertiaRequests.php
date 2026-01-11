<?php

namespace App\Http\Middleware;

use App\Http\Resources\UserResource;
use App\Models\Visitor;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        // Check if visitor has completed prompts (for banner display)
        $visitorHasCompletedPrompts = false;
        if (! $request->user()) {
            $visitorId = $request->cookie('visitor_id');
            if ($visitorId) {
                $visitor = Visitor::find($visitorId);
                $visitorHasCompletedPrompts = $visitor?->hasCompletedPrompts() ?? false;
            }
        }

        // Get country from route or detect
        $country = $request->route('country') ?? \App\Http\Middleware\SetCountry::detectCountry($request);
        $locale = app()->getLocale(); // Already set by SetCountry middleware

        // Resolve currency
        $setCountryMiddleware = new \App\Http\Middleware\SetCountry;
        $currency = $setCountryMiddleware->resolveCurrencyCode($country, $request);

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? UserResource::make($request->user())->resolve() : null,
            ],
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'warning' => fn () => $request->session()->get('warning'),
                'error' => fn () => $request->session()->get('error'),
                'previous_answer' => fn () => $request->session()->get('previous_answer'),
            ],
            'visitorHasCompletedPrompts' => $visitorHasCompletedPrompts,
            'subscription' => fn () => $request->user()?->getSubscriptionStatus(),
            'privacy' => fn () => $request->user()?->getPrivacyStatus(),
            'country' => fn () => $country,
            'locale' => fn () => $locale,
            'currency' => fn () => $currency,
            'direction' => fn () => SetCountry::getDirection($locale),
            'supportedLocales' => fn () => config('app.supported_locales'),
            'supportedCountries' => fn () => config('app.supported_countries', []),
        ];
    }
}
