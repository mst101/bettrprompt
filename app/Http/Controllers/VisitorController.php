<?php

namespace App\Http\Controllers;

use App\Models\Visitor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VisitorController extends Controller
{
    /**
     * Update visitor's UI complexity preference.
     */
    public function updateUiComplexity(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ui_complexity' => ['required', 'in:simple,advanced'],
        ]);

        $visitorId = $request->cookie('visitor_id');

        if ($visitorId) {
            $visitor = Visitor::find($visitorId);
            if ($visitor) {
                $visitor->update([
                    'ui_complexity' => $validated['ui_complexity'],
                ]);
            }
        }

        return back()->with('status', 'ui-complexity-updated');
    }

    /**
     * Update visitor's personality type.
     */
    public function updatePersonality(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'personalityType' => ['required', 'string', 'max:255'],
            'traitPercentages' => ['nullable', 'array'],
            'traitPercentages.mind' => ['nullable', 'integer', 'min:50', 'max:100'],
            'traitPercentages.energy' => ['nullable', 'integer', 'min:50', 'max:100'],
            'traitPercentages.nature' => ['nullable', 'integer', 'min:50', 'max:100'],
            'traitPercentages.tactics' => ['nullable', 'integer', 'min:50', 'max:100'],
            'traitPercentages.identity' => ['nullable', 'integer', 'min:50', 'max:100'],
        ]);

        $visitorId = $request->cookie('visitor_id');

        if ($visitorId) {
            $visitor = Visitor::find($visitorId);
            if ($visitor) {
                $visitor->update([
                    'personality_type' => $validated['personalityType'],
                    'trait_percentages' => $validated['traitPercentages'] ?? null,
                ]);
            }
        }

        return back()->with('status', 'personality-updated');
    }
}
