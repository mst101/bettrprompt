<?php

use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\MockN8nController;
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
    return Inertia::render('Legal/Terms');
})->name('terms');

Route::get('/privacy', function () {
    return Inertia::render('Legal/Privacy');
})->name('privacy');

Route::get('/cookies', function () {
    return Inertia::render('Legal/Cookies');
})->name('cookies');

// Google OAuth routes
Route::get('/auth/google', [OAuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [OAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

// Commented out the original dashboard route and the redundant alias
// Users now navigate directly to /prompt-builder instead
// Route::get('/dashboard', function () {
//    return Inertia::render('Dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

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
    Route::post('/profile/location/detect',
        [ProfileController::class, 'detectLocation'])->name('profile.location.detect');
    Route::delete('/profile/location', [ProfileController::class, 'clearLocation'])->name('profile.location.clear');
    // Professional profile routes
    Route::patch('/profile/professional',
        [ProfileController::class, 'updateProfessional'])->name('profile.professional.update');
    Route::delete('/profile/professional', [ProfileController::class, 'clearProfessional'])->name('profile.professional.clear');
    // Team profile routes
    Route::patch('/profile/team', [ProfileController::class, 'updateTeam'])->name('profile.team.update');
    Route::delete('/profile/team', [ProfileController::class, 'clearTeam'])->name('profile.team.clear');
    // Budget profile routes
    Route::patch('/profile/budget', [ProfileController::class, 'updateBudget'])->name('profile.budget.update');
    Route::delete('/profile/budget', [ProfileController::class, 'clearBudget'])->name('profile.budget.clear');
    // Tools profile routes
    Route::patch('/profile/tools', [ProfileController::class, 'updateTools'])->name('profile.tools.update');
    Route::delete('/profile/tools', [ProfileController::class, 'clearTools'])->name('profile.tools.clear');
    // Account deletion
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Prompt history (requires authentication)
    Route::get('/history', [PromptBuilderController::class, 'history'])
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
Route::post('/prompt-builder/analyse', [PromptBuilderController::class, 'preAnalyse'])
    ->name('prompt-builder.pre-analyse');
Route::post('/prompt-builder/{promptRun}/pre-analysis-answers',
    [PromptBuilderController::class, 'submitPreAnalysisAnswers'])
    ->name('prompt-builder.pre-analysis-answers');
Route::post('/prompt-builder/{promptRun}/update-quick-queries', [PromptBuilderController::class, 'updateQuickQueries'])
    ->name('prompt-builder.update-quick-queries');
Route::get('/prompt-builder/{promptRun}', [PromptBuilderController::class, 'analyse'])
    ->name('prompt-builder.analyse');
Route::get('/api/prompt-builder/{promptRun}', [PromptBuilderController::class, 'getFullDetails'])
    ->name('prompt-builder.full-details');
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
Route::post('/prompt-builder/{parentPromptRun}/create-child-from-task',
    [PromptBuilderController::class, 'createChild'])
    ->name('prompt-builder.create-child-from-task');
Route::post('/prompt-builder/{parentPromptRun}/create-child-from-answers',
    [PromptBuilderController::class, 'createChildFromAnswers'])
    ->name('prompt-builder.create-child-from-answers');
Route::post('/prompt-builder/{promptRun}/create-child-with-framework',
    [PromptBuilderController::class, 'switchFramework'])
    ->name('prompt-builder.create-child-with-framework');
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

    // Create user if it doesn't exist (for tests with unique users)
    if (! $user) {
        $user = \App\Models\User::create([
            'email' => $request->email,
            'name' => $request->input('name', 'Test User'),
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
        ]);
    }

    \Illuminate\Support\Facades\Auth::login($user);

    // Respond with success - session cookie is set by Laravel automatically
    return response()->json(['success' => true]);
})->name('test.login');

// Test OAuth endpoint for E2E tests
// Allows tests to authenticate with a mock Google account without actual OAuth flow
Route::post('/test/oauth-login', function (Illuminate\Http\Request $request) {
    // Note: CSRF protection is bypassed via middleware exemption for test routes

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
Route::post('/test/broadcast/analysis-completed/{promptRunId}',
    [\App\Http\Controllers\TestBroadcastController::class, 'triggerAnalysisCompleted'])
    ->name('test.broadcast.analysis-completed');
Route::post('/test/broadcast/prompt-optimization-completed/{promptRunId}',
    [\App\Http\Controllers\TestBroadcastController::class, 'triggerPromptOptimizationCompleted'])
    ->name('test.broadcast.prompt-optimization-completed');
Route::get('/test/echo-info', [\App\Http\Controllers\TestBroadcastController::class, 'echoInfo'])
    ->name('test.echo-info');
Route::post('/test/create-prompt-run', [\App\Http\Controllers\TestBroadcastController::class, 'createTestPromptRun'])
    ->name('test.create-prompt-run');
Route::post('/test/set-personality', [\App\Http\Controllers\TestBroadcastController::class, 'setPersonalityType'])
    ->name('test.set-personality');

// Test broadcast trigger for debugging WebSocket issues
Route::post('/test/broadcast-event/{promptRunId}', function ($promptRunId) {
    $promptRun = \App\Models\PromptRun::find($promptRunId);
    if ($promptRun) {
        \Illuminate\Support\Facades\Log::info('Test endpoint: Broadcasting PreAnalysisCompleted event', [
            'prompt_run_id' => $promptRun->id,
        ]);
        event(new \App\Events\PreAnalysisCompleted($promptRun));

        return response()->json(['success' => true, 'message' => 'Event broadcasted']);
    }

    return response()->json(['success' => false, 'message' => 'Prompt run not found'], 404);
})->name('test.broadcast-event')->withoutMiddleware('Illuminate\Foundation\Http\Middleware\VerifyCsrfToken');

// Workflow management system (admin only)
Route::middleware(['auth', 'admin'])->group(function () {
    // Workflow index page
    Route::get('/workflow', [\App\Http\Controllers\ReferenceDocumentsController::class, 'workflowIndex'])
        ->name('workflow.index');

    // Reference documents management
    Route::get('/workflow/docs', [\App\Http\Controllers\ReferenceDocumentsController::class, 'index'])
        ->name('workflow.docs.index');

    Route::get('/workflow/docs/api/list', [\App\Http\Controllers\ReferenceDocumentsController::class, 'list'])
        ->name('workflow.docs.list');

    Route::get('/workflow/docs/api/{type}/{filename}',
        [\App\Http\Controllers\ReferenceDocumentsController::class, 'show'])
        ->name('workflow.docs.show')
        ->where(['type' => 'core|framework', 'filename' => '[^/]+']);

    Route::post('/workflow/docs/api/{type}/{filename}',
        [\App\Http\Controllers\ReferenceDocumentsController::class, 'update'])
        ->name('workflow.docs.update')
        ->where(['type' => 'core|framework', 'filename' => '[^/]+']);

    // Debug n8n workflow - allows inspection of workflow input/output
    // Access at: https://app.localhost/workflow/0, /workflow/1, /workflow/2
    Route::get('/workflow/{workflowNumber}', [\App\Http\Controllers\DebugN8nController::class, 'show'])
        ->name('workflow.show')
        ->where('workflowNumber', '[0-9]+');

    // Set variant preference for workflow
    Route::post('/debug/workflow/{workflowNumber}/variant',
        [\App\Http\Controllers\DebugN8nController::class, 'setVariant'])
        ->name('workflow.set-variant')
        ->where('workflowNumber', '[0-9]+');

    // Debug API endpoints
    Route::post('/debug/workflow/{workflowNumber}/input',
        [\App\Http\Controllers\DebugN8nController::class, 'saveInput'])
        ->name('workflow.save-input')
        ->where('workflowNumber', '[0-9]+');

    Route::post('/debug/workflow/{workflowNumber}/pass-input/{passNumber}',
        [\App\Http\Controllers\DebugN8nController::class, 'savePassInput'])
        ->name('workflow.save-pass-input')
        ->where('workflowNumber', '[0-9]+')
        ->where('passNumber', '[0-9]+');

    Route::post('/debug/workflow/{workflowNumber}/javascript-old',
        [\App\Http\Controllers\DebugN8nController::class, 'saveOldJavaScript'])
        ->name('workflow.save-javascript-old')
        ->where('workflowNumber', '[0-9]+');

    Route::post('/debug/workflow/{workflowNumber}/javascript-new',
        [\App\Http\Controllers\DebugN8nController::class, 'saveNewJavaScript'])
        ->name('workflow.save-javascript-new')
        ->where('workflowNumber', '[0-9]+');

    Route::post('/debug/workflow/{workflowNumber}/reload-javascript-old',
        [\App\Http\Controllers\DebugN8nController::class, 'reloadJavaScriptFromWorkflow'])
        ->name('workflow.reload-javascript-old')
        ->where('workflowNumber', '[0-9]+');

    Route::post('/debug/workflow/{workflowNumber}/reload-javascript-new',
        [\App\Http\Controllers\DebugN8nController::class, 'reloadJavaScriptFromWorkflowAsNew'])
        ->name('workflow.reload-javascript-new')
        ->where('workflowNumber', '[0-9]+');

    Route::post('/debug/workflow/{workflowNumber}/prepare-prompt-old',
        [\App\Http\Controllers\DebugN8nController::class, 'preparePromptOld'])
        ->name('workflow.prepare-prompt-old')
        ->where('workflowNumber', '[0-9]+');

    Route::post('/debug/workflow/{workflowNumber}/prepare-prompt-new',
        [\App\Http\Controllers\DebugN8nController::class, 'preparePromptNew'])
        ->name('workflow.prepare-prompt-new')
        ->where('workflowNumber', '[0-9]+');

    Route::post('/debug/workflow/{workflowNumber}/save-to-n8n',
        [\App\Http\Controllers\DebugN8nController::class, 'saveJavaScriptToN8nWorkflow'])
        ->name('workflow.save-to-n8n')
        ->where('workflowNumber', '[0-9]+');

    Route::post('/debug/workflow/{workflowNumber}/upload-to-n8n-old',
        [\App\Http\Controllers\DebugN8nController::class, 'uploadOldWorkflowToN8n'])
        ->name('workflow.upload-to-n8n-old')
        ->where('workflowNumber', '[0-9]+');

    Route::post('/debug/workflow/{workflowNumber}/upload-to-n8n-new',
        [\App\Http\Controllers\DebugN8nController::class, 'uploadNewWorkflowToN8n'])
        ->name('workflow.upload-to-n8n-new')
        ->where('workflowNumber', '[0-9]+');

    Route::post('/debug/workflow/{workflowNumber}/execute-workflow-old',
        [\App\Http\Controllers\DebugN8nController::class, 'executeOldWorkflow'])
        ->name('workflow.execute-workflow-old')
        ->where('workflowNumber', '[0-9]+');

    Route::post('/debug/workflow/{workflowNumber}/execute-workflow-new',
        [\App\Http\Controllers\DebugN8nController::class, 'executeNewWorkflow'])
        ->name('workflow.execute-workflow-new')
        ->where('workflowNumber', '[0-9]+');

    Route::post('/debug/workflow/{workflowNumber}/upload-to-live',
        [\App\Http\Controllers\DebugN8nController::class, 'uploadWorkflowToLive'])
        ->name('workflow.upload-to-live')
        ->where('workflowNumber', '[0-9]+');
});

// Mock n8n webhook endpoints for E2E testing (only in e2e environment)
// These endpoints simulate n8n responses without requiring n8n to be running.
// When N8N_URL=http://localhost, PromptFrameworkService calls these instead of real n8n.
if (config('app.env') === 'e2e') {
    Route::post('/webhook/api/n8n/webhook/pre-analysis', [MockN8nController::class, 'workflow0'])
        ->name('test.n8n.workflow0');
    Route::post('/webhook/api/n8n/webhook/analysis', [MockN8nController::class, 'workflow1'])
        ->name('test.n8n.workflow1');
    Route::post('/webhook/api/n8n/webhook/generate', [MockN8nController::class, 'workflow2'])
        ->name('test.n8n.workflow2');
}
