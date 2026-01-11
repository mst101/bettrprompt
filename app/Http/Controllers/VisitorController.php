<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateVisitorPersonalityRequest;
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

        $visitorId = $request->cookie('visitor_id');

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

        $visitorId = $request->cookie('visitor_id');
        if ($visitorId) {
            $visitor = Visitor::find($visitorId);
            $visitor?->update(['language_code' => $validated['language_code']]);

            // Invalidate language cache so middleware fetches fresh value on next request
            Cache::forget("visitor.{$visitorId}.language");
        }

        return response()->json(['success' => true]);
    }

    /**
     * Update currency preference for authenticated users and visitors
     */
    public function updateCurrency(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'currency_code' => [
                'required',
                'string',
                'size:3',
                Rule::in(['GBP', 'EUR', 'USD']),
            ],
        ]);

        if ($request->user()) {
            // Authenticated user: update user record
            $request->user()->update([
                'currency_code' => $validated['currency_code'],
            ]);

            // Also sync all visitor records linked to this user
            Visitor::where('user_id', $request->user()->id)
                ->update(['currency_code' => $validated['currency_code']]);
        } else {
            // Visitor: update session and database if visitor exists
            session(['currency_code' => $validated['currency_code']]);

            $visitorId = $request->cookie('visitor_id');
            if ($visitorId) {
                $visitor = Visitor::find($visitorId);
                $visitor?->update(['currency_code' => $validated['currency_code']]);
            }
        }

        return back();
    }
}
