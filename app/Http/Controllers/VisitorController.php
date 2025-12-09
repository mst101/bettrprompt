<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateVisitorPersonalityRequest;
use App\Http\Requests\UpdateVisitorUiComplexityRequest;
use App\Models\Visitor;
use Illuminate\Http\RedirectResponse;

class VisitorController extends Controller
{
    /**
     * Update visitor's UI complexity preference.
     */
    public function updateUiComplexity(UpdateVisitorUiComplexityRequest $request): RedirectResponse
    {
        $validated = $request->validated();

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
    public function updatePersonality(UpdateVisitorPersonalityRequest $request): RedirectResponse
    {
        $validated = $request->validated();

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
