<?php

use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PromptBuilderController;
use App\Http\Controllers\VisitorController;
use App\Http\Controllers\VoiceTranscriptionController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'isReturningVisitor' => request()->cookie('returning_visitor') !== null,
        'modal' => request()->query('modal'),
    ]);
})->name('home');

Route::get('/terms', function () {
    return Inertia::render('Terms');
})->name('terms');

Route::get('/privacy', function () {
    return Inertia::render('Privacy');
})->name('privacy');

Route::get('/cookies', function () {
    return Inertia::render('Cookies');
})->name('cookies');

// Google OAuth routes
Route::get('/auth/google', [OAuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [OAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

// Route::get('/dashboard', function () {
//    return Inertia::render('Dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/dashboard', [PromptBuilderController::class, 'index'])
    ->name('dashboard');

// Feedback routes (no authentication required)
Route::get('/feedback/create', [FeedbackController::class, 'create'])
    ->name('feedback.create');
Route::get('/feedback', [FeedbackController::class, 'show'])
    ->name('feedback.show');
Route::post('/feedback', [FeedbackController::class, 'store'])
    ->name('feedback.store');
Route::put('/feedback', [FeedbackController::class, 'update'])
    ->name('feedback.update');
Route::get('/feedback/thank-you', [FeedbackController::class, 'thankYou'])
    ->name('feedback.thank-you');
Route::get('/feedback/download/{filename}', [FeedbackController::class, 'downloadPdf'])
    ->name('feedback.download-pdf');

// Voice transcription endpoint (no authentication required)
Route::post('/voice-transcription', [VoiceTranscriptionController::class, 'transcribe'])
    ->middleware('throttle:30,1');

// Visitor preferences (no authentication required)
Route::patch('/visitor/ui-complexity', [VisitorController::class, 'updateUiComplexity'])
    ->name('visitor.ui-complexity.update');
Route::patch('/visitor/personality', [VisitorController::class, 'updatePersonality'])
    ->name('visitor.personality.update');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/personality',
        [ProfileController::class, 'updatePersonality'])->name('profile.personality.update');
    Route::patch('/profile/ui-complexity',
        [ProfileController::class, 'updateUiComplexity'])->name('profile.ui-complexity.update');
    // Location profile routes
    Route::patch('/profile/location', [ProfileController::class, 'updateLocation'])->name('profile.location.update');
    Route::post('/profile/location/detect', [ProfileController::class, 'detectLocation'])->name('profile.location.detect');
    Route::delete('/profile/location', [ProfileController::class, 'clearLocation'])->name('profile.location.clear');
    // Professional profile routes
    Route::patch('/profile/professional', [ProfileController::class, 'updateProfessional'])->name('profile.professional.update');
    // Team profile routes
    Route::patch('/profile/team', [ProfileController::class, 'updateTeam'])->name('profile.team.update');
    // Budget profile routes
    Route::patch('/profile/budget', [ProfileController::class, 'updateBudget'])->name('profile.budget.update');
    // Tools profile routes
    Route::patch('/profile/tools', [ProfileController::class, 'updateTools'])->name('profile.tools.update');
    // Account deletion
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Prompt Builder history (requires authentication)
    Route::get('/prompt-builder-history', [PromptBuilderController::class, 'history'])
        ->name('prompt-builder.history');
});

// Admin routes (requires authentication and admin role)
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\AdminController::class, 'index'])->name('dashboard');

    // Users
    Route::get('/users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'show'])->name('users.show');

    // Tasks
    Route::get('/tasks', [\App\Http\Controllers\Admin\TaskController::class, 'index'])->name('tasks.index');
    Route::get('/tasks/{taskId}',
        [\App\Http\Controllers\Admin\TaskController::class, 'show'])->name('tasks.show')->where('taskId', '[0-9]+');

    // Prompt Runs
    Route::get('/prompt-runs/{promptRun}',
        [\App\Http\Controllers\Admin\TaskController::class, 'promptRun'])->name('prompt-runs.show');
});

require __DIR__.'/auth.php';

// Route::middleware(['auth'])->group(function () {
Route::get('/prompt-builder', [PromptBuilderController::class, 'index'])
    ->name('prompt-builder.index');
Route::post('/prompt-builder/analyse', [PromptBuilderController::class, 'analyse'])
    ->name('prompt-builder.analyse');
Route::post('/prompt-builder/{promptRun}/pre-analysis-answers', [PromptBuilderController::class, 'submitPreAnalysisAnswers'])
    ->name('prompt-builder.pre-analysis-answers');
Route::post('/prompt-builder/{promptRun}/update-quick-queries', [PromptBuilderController::class, 'updateQuickQueries'])
    ->name('prompt-builder.update-quick-queries');
Route::get('/prompt-builder/{promptRun}', [PromptBuilderController::class, 'show'])
    ->name('prompt-builder.show');
Route::post('/prompt-builder/{promptRun}/answer', [PromptBuilderController::class, 'answerQuestion'])
    ->name('prompt-builder.answer');
Route::post('/prompt-builder/{promptRun}/skip', [PromptBuilderController::class, 'skipQuestion'])
    ->name('prompt-builder.skip');
Route::post('/prompt-builder/{promptRun}/go-back', [PromptBuilderController::class, 'goBackToPreviousQuestion'])
    ->name('prompt-builder.go-back');
Route::post('/prompt-builder/{promptRun}/retry', [PromptBuilderController::class, 'retry'])
    ->name('prompt-builder.retry');
Route::post('/prompt-builder/{promptRun}/generate', [PromptBuilderController::class, 'generate'])
    ->name('prompt-builder.generate');
Route::post('/prompt-builder/{parentPromptRun}/create-child-from-answers', [PromptBuilderController::class, 'createChildFromAnswers'])
    ->name('prompt-builder.create-child-from-answers');
Route::post('/prompt-builder/{parentPromptRun}/create-child', [PromptBuilderController::class, 'createChild'])
    ->name('prompt-builder.create-child');
Route::post('/prompt-builder/{promptRun}/switch-framework', [PromptBuilderController::class, 'switchFramework'])
    ->name('prompt-builder.switch-framework');
Route::delete('/prompt-builder/{promptRun}', [PromptBuilderController::class, 'destroy'])
    ->name('prompt-builder.destroy');
Route::post('/prompt-builder/{promptRun}/answer', [PromptBuilderController::class, 'answerQuestion'])
    ->name('prompt-builder.answer');
Route::post('/prompt-builder/{promptRun}/skip', [PromptBuilderController::class, 'skipQuestion'])
    ->name('prompt-builder.skip');
Route::patch('/prompt-builder/{promptRun}/update-prompt', [PromptBuilderController::class, 'updateOptimizedPrompt'])
    ->name('prompt-builder.update-prompt');
// });

// E2E Test-Only Routes
// These routes provide a backdoor for E2E tests to authenticate without using the modal form
// They check for a special header to ensure they're only used by Playwright tests
// CSRF is automatically skipped for requests with X-Test-Auth header (see VerifyCsrfToken middleware)
Route::post('/test/login', function (Illuminate\Http\Request $request) {
    // Only allow if the request includes the test auth header
    // This header is set by Playwright tests to prove they're authorized to use this endpoint
    if ($request->header('X-Test-Auth') !== 'playwright-e2e-tests') {
        abort(403, 'Unauthorized');
    }

    $user = \App\Models\User::where('email', $request->email)->first();

    if (! $user) {
        return response()->json(['error' => 'User not found'], 404);
    }

    \Illuminate\Support\Facades\Auth::login($user);

    // Respond with success - session cookie is set by Laravel automatically
    return response()->json(['success' => true]);
})->name('test.login');

// Test OAuth endpoint for E2E tests
// Allows tests to authenticate with a mock Google account without actual OAuth flow
Route::post('/test/oauth-login', function (Illuminate\Http\Request $request) {
    // Only allow if the request includes the test auth header
    if ($request->header('X-Test-Auth') !== 'playwright-e2e-tests') {
        abort(403, 'Unauthorized');
    }

    $email = $request->input('email', 'oauth-test@example.com');
    $name = $request->input('name', 'OAuth Test User');
    $googleId = $request->input('google_id', 'test-google-id-'.uniqid());

    // Find or create user with Google ID
    $user = \App\Models\User::where('google_id', $googleId)
        ->orWhere('email', $email)
        ->first();

    if (! $user) {
        // Create new user from OAuth data
        $user = \App\Models\User::create([
            'name' => $name,
            'email' => $email,
            'google_id' => $googleId,
            'password' => bcrypt('oauth-'.$googleId),
        ]);
    } else {
        // Update existing user with google_id if not already set
        $user->update([
            'google_id' => $googleId,
            'name' => $name,
        ]);
    }

    \Illuminate\Support\Facades\Auth::login($user);

    return response()->json(['success' => true, 'user_id' => $user->id]);
})->name('test.oauth-login');

// E2E Test-Only Broadcast Routes
// These routes allow E2E tests to trigger WebSocket events manually
Route::post('/test/broadcast/analysis-completed/{promptRunId}', [\App\Http\Controllers\TestBroadcastController::class, 'triggerAnalysisCompleted'])
    ->name('test.broadcast.analysis-completed');
Route::post('/test/broadcast/prompt-optimization-completed/{promptRunId}', [\App\Http\Controllers\TestBroadcastController::class, 'triggerPromptOptimizationCompleted'])
    ->name('test.broadcast.prompt-optimization-completed');
Route::get('/test/echo-info', [\App\Http\Controllers\TestBroadcastController::class, 'echoInfo'])
    ->name('test.echo-info');
Route::post('/test/create-prompt-run', [\App\Http\Controllers\TestBroadcastController::class, 'createTestPromptRun'])
    ->name('test.create-prompt-run');
Route::post('/test/set-personality', [\App\Http\Controllers\TestBroadcastController::class, 'setPersonalityType'])
    ->name('test.set-personality');

// Mock n8n webhook endpoints for E2E testing
// These endpoints simulate n8n responses without requiring n8n to be running
// The .env.e2e file should have N8N_URL=http://localhost (pointing to these routes)
Route::post('/webhook/api/n8n/webhook/pre-analysis', [\App\Http\Controllers\MockN8nController::class, 'preAnalysis'])
    ->name('test.n8n.pre-analysis');
Route::post('/webhook/api/n8n/webhook/analyse', [\App\Http\Controllers\MockN8nController::class, 'analyse'])
    ->name('test.n8n.analyse');
Route::post('/webhook/api/n8n/webhook/optimise-prompt', [\App\Http\Controllers\MockN8nController::class, 'optimisePrompt'])
    ->name('test.n8n.optimise-prompt');
