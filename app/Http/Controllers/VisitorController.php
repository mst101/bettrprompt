<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateVisitorPersonalityRequest;
use App\Models\Visitor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        }

        return response()->json(['success' => true]);
    }
}
