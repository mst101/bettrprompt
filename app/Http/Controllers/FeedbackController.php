<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFeedbackRequest;
use App\Services\PersonalityTypeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
        $validated = $request->validated();

        DB::table('feedback')->insert([
            'user_id' => auth()->id(),
            'experience_level' => $validated['experience_level'],
            'usefulness' => $validated['usefulness'],
            'usage_intent' => $validated['usage_intent'],
            'suggestions' => $validated['suggestions'] ?? null,
            'desired_features' => json_encode($validated['desired_features']),
            'desired_features_other' => $validated['desired_features_other'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('feedback.thank-you');
    }

    public function update(StoreFeedbackRequest $request)
    {
        $validated = $request->validated();

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

        return redirect()->route('prompt-optimizer.index')
            ->with('success', 'Thank you for updating your feedback!');
    }

    public function thankYou(): Response
    {
        $user = auth()->user();
        $personalityType = $user->personality_type;

        // Get available PDFs for the user's personality type
        $availablePdfs = PersonalityTypeService::getAvailablePdfs($personalityType);

        // Get or generate referral code
        $referralCode = $user->getReferralCode();
        $referralUrl = route('home', ['ref' => $referralCode]);

        return Inertia::render('Feedback/ThankYou', [
            'personalityType' => $personalityType,
            'personalityTypeName' => PersonalityTypeService::getFolderName($personalityType),
            'availablePdfs' => $availablePdfs,
            'referralUrl' => $referralUrl,
        ]);
    }

    public function downloadPdf(string $filename): BinaryFileResponse|RedirectResponse
    {
        $user = auth()->user();
        $personalityType = $user->personality_type;
        $folderName = PersonalityTypeService::getFolderName($personalityType);

        if (! $folderName) {
            abort(404, 'Personality type not found');
        }

        $filePath = resource_path("pdf/{$folderName}/{$filename}");

        if (! file_exists($filePath)) {
            abort(404, 'PDF not found');
        }

        // Security check: ensure the file is actually in the user's personality type folder
        $realPath = realpath($filePath);
        $allowedPath = realpath(resource_path("pdf/{$folderName}"));

        if (! str_starts_with($realPath, $allowedPath)) {
            abort(403, 'Unauthorised access');
        }

        return response()->download($filePath);
    }
}
