<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class FeedbackController extends Controller
{
    public function create(): Response|RedirectResponse
    {
        // Check if user has already submitted feedback
        $existingFeedback = DB::table('feedback')
            ->where('user_id', auth()->id())
            ->first();

        if ($existingFeedback) {
            return redirect()->route('feedback.show');
        }

        return Inertia::render('Feedback/Create');
    }

    public function show(): Response|RedirectResponse
    {
        $feedback = DB::table('feedback')
            ->where('user_id', auth()->id())
            ->first();

        if (! $feedback) {
            return redirect()->route('feedback.create');
        }

        return Inertia::render('Feedback/Show', [
            'feedback' => [
                'experienceLevel' => $feedback->experience_level,
                'usefulness' => $feedback->usefulness,
                'recommendationLikelihood' => $feedback->recommendation_likelihood,
                'suggestions' => $feedback->suggestions,
                'desiredFeatures' => json_decode($feedback->desired_features, true),
                'desiredFeaturesOther' => $feedback->desired_features_other,
                'createdAt' => $feedback->created_at,
                'updatedAt' => $feedback->updated_at,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'experienceLevel' => ['required', 'integer', 'min:1', 'max:7'],
            'usefulness' => ['required', 'integer', 'min:1', 'max:7'],
            'recommendationLikelihood' => ['required', 'integer', 'min:1', 'max:7'],
            'suggestions' => ['nullable', 'string', 'max:5000'],
            'desiredFeatures' => ['required', 'array', 'min:1'],
            'desiredFeatures.*' => [
                'string', 'in:templates,compare,api-integration,collaboration,model-specific,other',
            ],
            'desiredFeaturesOther' => ['required_if:desiredFeatures.*,other', 'nullable', 'string', 'max:500'],
        ]);

        DB::table('feedback')->insert([
            'user_id' => auth()->id(),
            'experience_level' => $validated['experienceLevel'],
            'usefulness' => $validated['usefulness'],
            'recommendation_likelihood' => $validated['recommendationLikelihood'],
            'suggestions' => $validated['suggestions'] ?? null,
            'desired_features' => json_encode($validated['desiredFeatures']),
            'desired_features_other' => $validated['desiredFeaturesOther'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('prompt-optimizer.index')
            ->with('success', 'Thank you for your feedback!');
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'experienceLevel' => ['required', 'integer', 'min:1', 'max:7'],
            'usefulness' => ['required', 'integer', 'min:1', 'max:7'],
            'recommendationLikelihood' => ['required', 'integer', 'min:1', 'max:7'],
            'suggestions' => ['nullable', 'string', 'max:5000'],
            'desiredFeatures' => ['required', 'array', 'min:1'],
            'desiredFeatures.*' => [
                'string', 'in:templates,compare,api-integration,collaboration,model-specific,other',
            ],
            'desiredFeaturesOther' => ['required_if:desiredFeatures.*,other', 'nullable', 'string', 'max:500'],
        ]);

        DB::table('feedback')
            ->where('user_id', auth()->id())
            ->update([
                'experience_level' => $validated['experienceLevel'],
                'usefulness' => $validated['usefulness'],
                'recommendation_likelihood' => $validated['recommendationLikelihood'],
                'suggestions' => $validated['suggestions'] ?? null,
                'desired_features' => json_encode($validated['desiredFeatures']),
                'desired_features_other' => $validated['desiredFeaturesOther'] ?? null,
                'updated_at' => now(),
            ]);

        return redirect()->route('prompt-optimizer.index')
            ->with('success', 'Thank you for updating your feedback!');
    }
}
