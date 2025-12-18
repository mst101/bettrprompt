<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFeedbackRequest;
use Illuminate\Http\RedirectResponse;
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
                'usageIntent' => $feedback->usage_intent,
                'suggestions' => $feedback->suggestions,
                'desiredFeatures' => json_decode($feedback->desired_features, true),
                'desiredFeaturesOther' => $feedback->desired_features_other,
                'createdAt' => $feedback->created_at,
                'updatedAt' => $feedback->updated_at,
            ],
        ]);
    }

    public function store(StoreFeedbackRequest $request)
    {
        $validated = $request->validatedToSnakeCase();

        DB::table('feedback')->insert([
            'user_id' => auth()->id(),
            'personality_type' => auth()->user()?->personality_type,
            'experience_level' => $validated['experience_level'],
            'usefulness' => $validated['usefulness'],
            'usage_intent' => $validated['usage_intent'],
            'suggestions' => $validated['suggestions'] ?? null,
            'desired_features' => json_encode($validated['desired_features']),
            'desired_features_other' => $validated['desired_features_other'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('feedback.thank-you')
            ->with('success', 'Thank you for your feedback!');
    }

    public function update(StoreFeedbackRequest $request)
    {
        $validated = $request->validatedToSnakeCase();

        DB::table('feedback')
            ->where('user_id', auth()->id())
            ->update([
                'experience_level' => $validated['experience_level'],
                'usefulness' => $validated['usefulness'],
                'usage_intent' => $validated['usage_intent'],
                'suggestions' => $validated['suggestions'] ?? null,
                'desired_features' => json_encode($validated['desired_features']),
                'desired_features_other' => $validated['desired_features_other'] ?? null,
                'updated_at' => now(),
            ]);

        return redirect()->route('feedback.show')
            ->with('success', 'Thank you for updating your feedback!');
    }

    public function thankYou(): Response|RedirectResponse
    {
        $feedback = DB::table('feedback')
            ->where('user_id', auth()->id())
            ->first();

        if (! $feedback) {
            return redirect()->route('feedback.create');
        }

        // Get or generate referral code
        $user = auth()->user();
        $referralCode = $user->getReferralCode();
        $referralUrl = route('home', ['ref' => $referralCode]);

        return Inertia::render('Feedback/ThankYou', [
            'referralUrl' => $referralUrl,
        ]);
    }
}
