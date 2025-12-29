<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateVisitorPersonalityRequest;
use App\Models\Visitor;
use Illuminate\Http\RedirectResponse;

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
}
