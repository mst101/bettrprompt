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

        return redirect()->route('feedback.show')
            ->with('success', 'Thank you for updating your feedback!');
    }

    public function thankYou(): Response
    {
        $feedback = DB::table('feedback')
            ->where('user_id', auth()->id())
            ->first();

        if (! $feedback) {
            return redirect()->route('feedback.create');
        }

        // Use the personality type from when feedback was submitted
        $personalityType = $feedback->personality_type;

        if (! $personalityType) {
            return redirect()->route('profile.edit')
                ->with('error', 'Please complete your personality type profile before downloading resources.');
        }

        // Get available PDFs for the personality type at time of feedback
        $availablePdfs = PersonalityTypeService::getAvailablePdfs($personalityType);

        // Get or generate referral code
        $user = auth()->user();
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
        // Get the personality type from the user's feedback submission
        $feedback = DB::table('feedback')
            ->where('user_id', auth()->id())
            ->first();

        if (! $feedback) {
            abort(403, 'No feedback found. Please submit feedback before downloading resources.');
        }

        $personalityType = $feedback->personality_type;

        if (! $personalityType) {
            abort(403, 'Personality type not set in your feedback record.');
        }

        $folderName = PersonalityTypeService::getFolderName($personalityType);

        if (! $folderName) {
            abort(404, 'Personality type not found');
        }

        $filePath = resource_path("pdf/{$folderName}/{$filename}");

        if (! file_exists($filePath)) {
            abort(404, 'PDF not found');
        }

        // Security check: ensure the file is actually in the personality type folder from feedback
        $realPath = realpath($filePath);
        $allowedPath = realpath(resource_path("pdf/{$folderName}"));

        if (! str_starts_with($realPath, $allowedPath)) {
            abort(403, 'Unauthorised access');
        }

        return response()->download($filePath);
    }
}
