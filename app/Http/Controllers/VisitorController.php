<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetCountry;
use App\Http\Requests\UpdateLocationRequest;
use App\Http\Requests\UpdateVisitorPersonalityRequest;
use App\Models\Currency;
use App\Models\Visitor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class VisitorController extends Controller
{
    /**
     * Update visitor's personality type.
     */
    public function updatePersonality(UpdateVisitorPersonalityRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $visitorId = getVisitorIdFromCookie($request);

        if ($visitorId) {
            $visitor = Visitor::find($visitorId);
            $visitor?->update([
                'personality_type' => $validated['personalityType'],
                'trait_percentages' => $validated['traitPercentages'] ?? null,
            ]);
        }

        return back()->with('status', 'personality-updated');
    }

    /**
     * Update visitor's language preference.
     */
    public function updateLanguage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'language_code' => ['required', 'string', 'max:10', Rule::in(config('app.supported_locales'))],
        ]);

        $visitorId = getVisitorIdFromCookie($request);
        if ($visitorId) {
            $visitor = Visitor::find($visitorId);
            $visitor?->update(['language_code' => $validated['language_code']]);

            // Invalidate language cache - language is global so only need simple key
            Cache::forget("visitor.{$visitorId}.language");
        }

        return response()->json(['success' => true]);
    }

    /**
     * Update currency preference for authenticated users and visitors
     */
    public function updateCurrency(Request $request): RedirectResponse
    {
        $activeCurrencies = Currency::where('active', true)->pluck('id')->all();

        $validated = $request->validate([
            'currency_code' => [
                'required',
                'string',
                'size:3',
                Rule::in($activeCurrencies),
            ],
        ]);

        $currencyCode = $validated['currency_code'];

        if ($request->user()) {
            // Authenticated user: update user record
            $request->user()->update([
                'currency_code' => $currencyCode,
            ]);

            // Also sync all visitor records linked to this user
            Visitor::where('user_id', $request->user()->id)
                ->update(['currency_code' => $currencyCode]);

            // Also sync visitor from cookie if present (e.g., user was visitor before authenticating)
            $visitorId = getVisitorIdFromCookie($request);
            if ($visitorId) {
                $visitor = Visitor::find($visitorId);
                $visitor?->update(['currency_code' => $currencyCode]);
            }

            // Invalidate currency caches (route-specific)
            SetCountry::clearCachePattern("user.{$request->user()->id}.currency.*");
        } else {
            // Visitor: update database if visitor exists
            $visitorId = getVisitorIdFromCookie($request);
            if ($visitorId) {
                $visitor = Visitor::find($visitorId);
                if ($visitor) {
                    $visitor->update(['currency_code' => $currencyCode]);

                    // Invalidate currency cache (route-specific)
                    SetCountry::clearCachePattern("visitor.{$visitorId}.currency.*");
                }
            }
        }

        // Redirect back to pricing page - Inertia will process this and reload with new currency
        return back();
    }

    /**
     * Update visitor's location preference.
     */
    public function updateLocation(UpdateLocationRequest $request): JsonResponse
    {
        $visitorId = getVisitorIdFromCookie($request);
        if (! $visitorId) {
            return response()->json(['success' => false], 404);
        }

        $visitor = Visitor::find($visitorId);
        if (! $visitor) {
            return response()->json(['success' => false], 404);
        }

        $validated = $request->validated();
        $fields = [
            'country_code',
            'region',
            'city',
            'timezone',
            'currency_code',
            'language_code',
        ];
        $updates = [];
        foreach ($fields as $field) {
            if (array_key_exists($field, $validated)) {
                if ($field === 'language_code') {
                    $updates[$field] = $validated[$field]
                        ? (SetCountry::normalizeLocaleToSupported($validated[$field]) ?? $validated[$field])
                        : null;
                } else {
                    $updates[$field] = $validated[$field];
                }
            }
        }

        if (! empty($updates)) {
            $visitor->update($updates);
        }

        if (array_key_exists('language_code', $updates)) {
            Cache::forget("visitor.{$visitorId}.language");
        }
        if (array_key_exists('currency_code', $updates)) {
            SetCountry::clearCachePattern("visitor.{$visitorId}.currency.*");
        }

        return response()->json(['success' => true]);
    }

    /**
     * Clear visitor location data.
     */
    public function clearLocation(Request $request): JsonResponse
    {
        $visitorId = getVisitorIdFromCookie($request);
        if (! $visitorId) {
            return response()->json(['success' => false], 404);
        }

        $visitor = Visitor::find($visitorId);
        if (! $visitor) {
            return response()->json(['success' => false], 404);
        }

        $visitor->update([
            'country_code' => null,
            'region' => null,
            'city' => null,
            'timezone' => null,
            'currency_code' => null,
            'latitude' => null,
            'longitude' => null,
            'language_code' => null,
            'location_detected_at' => null,
        ]);

        // Invalidate language cache (language is global)
        Cache::forget("visitor.{$visitorId}.language");
        // Invalidate currency caches (currency is route-specific)
        SetCountry::clearCachePattern("visitor.{$visitorId}.currency.*");

        return response()->json(['success' => true]);
    }
}
