<?php

use App\Http\Controllers\Admin\ExperimentsController;
use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\MockN8nController;
use App\Http\Controllers\PrivacyController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PromptBuilderController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\VisitorController;
use App\Http\Controllers\VoiceTranscriptionController;
use App\Http\Middleware\SetCountry;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Root redirect to detected country
Route::get('/', function () {
    $country = SetCountry::detectCountry(request());

    return redirect("/{$country}");
});

// Country-prefixed routes (all user-facing pages)
// Note: SetCountry middleware runs globally in bootstrap/app.php before HandleInertiaRequests
Route::prefix('{country}')
    ->where(['country' => '[a-z]{2}'])
    ->group(function () {
        Route::get('/', function () {
            $visitorId = request()->cookie('visitor_id');
            $isReturningVisitor = false;

            if ($visitorId) {
                $visitor = \App\Models\Visitor::find($visitorId);

                // Determine if returning visitor:
                // - Created more than 1 hour ago, OR
                // - Has been updated since creation (last_visit_at > created_at by significant amount)
                if ($visitor) {
                    $isReturningVisitor = $visitor->isReturning();

                    // Fallback: if visitor exists and was created more than 5 minutes ago,
                    // they're likely returning (even if they cleared cookies and came back quickly)
                    if (! $isReturningVisitor && $visitor->created_at->diffInMinutes(now()) > 5) {
                        $isReturningVisitor = true;
                    }
                }
            }

            return Inertia::render('Home', [
                'canLogin' => Route::has('login'),
                'canRegister' => Route::has('register'),
                'isReturningVisitor' => $isReturningVisitor,
                'modal' => request()->query('modal'),
            ]);
        })->name('home');

        Route::get('/pilot', function () {
            return Inertia::render('Pilot', [
                'canLogin' => Route::has('login'),
                'canRegister' => Route::has('register'),
            ]);
        })->name('pilot');

        Route::get('/terms', function () {
            return Inertia::render('Legal/Terms');
        })->name('terms');

        Route::get('/privacy', function () {
            return Inertia::render('Legal/Privacy');
        })->name('privacy');

        Route::get('/cookies', function () {
            return Inertia::render('Legal/Cookies');
        })->name('cookies');

        // Pricing page (public)
        Route::get('/pricing', [SubscriptionController::class, 'pricing'])
            ->name('pricing');

        // Google OAuth routes
        Route::get('/auth/google', [OAuthController::class, 'redirectToGoogle'])->name('auth.google');
        Route::get('/auth/google/callback',
            [OAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

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
        Route::patch('/visitor/personality', [VisitorController::class, 'updatePersonality'])
            ->name('visitor.personality.update');
        Route::patch('/visitor/language', [VisitorController::class, 'updateLanguage'])
            ->name('visitor.language.update');
        Route::patch('/visitor/location', [VisitorController::class, 'updateLocation'])
            ->name('visitor.location.update');
        Route::delete('/visitor/location', [VisitorController::class, 'clearLocation'])
            ->name('visitor.location.clear');
        Route::post('/currency/select', [VisitorController::class, 'updateCurrency'])
            ->name('currency.select');

        // Prompt builder routes (public, supports guest visitors)
        Route::get('/prompt-builder', [PromptBuilderController::class, 'index'])
            ->name('prompt-builder.index');
        Route::post('/prompt-builder/analyse', [PromptBuilderController::class, 'preAnalyse'])
            ->name('prompt-builder.pre-analyse');
        Route::post('/prompt-builder/{promptRun}/pre-analysis-answers',
            [PromptBuilderController::class, 'analyse'])
            ->name('prompt-builder.pre-analysis-answers');
        Route::post('/prompt-builder/{promptRun}/update-pre-analysis-answers',
            [PromptBuilderController::class, 'updatePreAnalysisAnswers'])
            ->name('prompt-builder.update-pre-analysis-answers');
        Route::get('/prompt-builder/{promptRun}', [PromptBuilderController::class, 'show'])
            ->name('prompt-builder.show');
        Route::get('/api/prompt-builder/{promptRun}', [PromptBuilderController::class, 'getFullDetails'])
            ->name('prompt-builder.full-details');
        Route::post('/prompt-builder/{promptRun}/answer', [PromptBuilderController::class, 'answerQuestion'])
            ->name('prompt-builder.answer');
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
        Route::patch('/prompt-builder/{promptRun}/update-prompt',
            [PromptBuilderController::class, 'updateOptimizedPrompt'])
            ->name('prompt-builder.update-prompt');

        // Subscription routes (authenticated)
        Route::middleware(['auth'])->group(function () {
            // Checkout
            Route::post('/subscription/checkout', [SubscriptionController::class, 'checkout'])
                ->name('subscription.checkout');

            Route::get('/subscription/success', [SubscriptionController::class, 'success'])
                ->name('subscription.success');

            Route::get('/subscription/cancelled', [SubscriptionController::class, 'cancelled'])
                ->name('subscription.cancelled');

            // Subscription management
            Route::get('/settings/subscription', [SubscriptionController::class, 'show'])
                ->name('settings.subscription');

            Route::get('/billing-portal', [SubscriptionController::class, 'billingPortal'])
                ->name('billing.portal');

            Route::post('/subscription/cancel', [SubscriptionController::class, 'cancel'])
                ->name('subscription.cancel');

            Route::post('/subscription/resume', [SubscriptionController::class, 'resume'])
                ->name('subscription.resume');

            // Privacy settings routes
            Route::get('/settings/privacy', [PrivacyController::class, 'show'])
                ->name('settings.privacy');

            Route::post('/privacy/begin-setup', [PrivacyController::class, 'beginSetup'])
                ->name('privacy.begin-setup');

            Route::post('/privacy/confirm-setup', [PrivacyController::class, 'confirmSetup'])
                ->name('privacy.confirm-setup');

            Route::post('/privacy/unlock', [PrivacyController::class, 'unlock'])
                ->name('privacy.unlock');

            Route::get('/privacy/recovery', [PrivacyController::class, 'showRecovery'])
                ->name('privacy.recovery');

            Route::post('/privacy/recover', [PrivacyController::class, 'recover'])
                ->name('privacy.recover');

            Route::post('/privacy/update-password', [PrivacyController::class, 'updatePassword'])
                ->name('privacy.update-password');

            Route::post('/privacy/disable', [PrivacyController::class, 'disable'])
                ->name('privacy.disable');

            // Profile routes
            Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
            Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
            Route::patch('/profile/personality',
                [ProfileController::class, 'updatePersonality'])->name('profile.personality.update');
            Route::patch('/profile/ui-complexity',
                [ProfileController::class, 'updateUiComplexity'])->name('profile.ui-complexity.update');
            Route::patch('/profile/language',
                [ProfileController::class, 'updateLanguage'])->name('profile.language.update');
            Route::patch('/profile/location-prompt', [ProfileController::class, 'updateLocationPromptPreference'])
                ->name('profile.location.prompt');
            // Location profile routes
            Route::patch('/profile/location',
                [ProfileController::class, 'updateLocation'])->name('profile.location.update');
            Route::post('/profile/location/detect',
                [ProfileController::class, 'detectLocation'])->name('profile.location.detect');
            Route::delete('/profile/location',
                [ProfileController::class, 'clearLocation'])->name('profile.location.clear');
            // Professional profile routes
            Route::patch('/profile/professional',
                [ProfileController::class, 'updateProfessional'])->name('profile.professional.update');
            Route::delete('/profile/professional',
                [ProfileController::class, 'clearProfessional'])->name('profile.professional.clear');
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
            Route::get('/users/{user}',
                [\App\Http\Controllers\Admin\UserController::class, 'show'])->name('users.show');

            // Tasks
            Route::get('/tasks', [\App\Http\Controllers\Admin\TaskController::class, 'index'])->name('tasks.index');
            Route::get('/tasks/{taskId}',
                [\App\Http\Controllers\Admin\TaskController::class, 'show'])->name('tasks.show')->where('taskId',
                    '[0-9]+');

            // Prompt Runs
            Route::get('/prompt-runs/{promptRun}',
                [\App\Http\Controllers\Admin\TaskController::class, 'promptRun'])->name('prompt-runs.show');

            // Experiments
            Route::resource('experiments', ExperimentsController::class);
            Route::post('/experiments/{experiment}/launch',
                [ExperimentsController::class, 'launch'])->name('experiments.launch');
            Route::post('/experiments/{experiment}/pause',
                [ExperimentsController::class, 'pause'])->name('experiments.pause');
            Route::post('/experiments/{experiment}/resume',
                [ExperimentsController::class, 'resume'])->name('experiments.resume');
            Route::post('/experiments/{experiment}/complete',
                [ExperimentsController::class, 'complete'])->name('experiments.complete');

            // Domain Analytics
            Route::get('/domain-analytics', [
                \App\Http\Controllers\Admin\AdminController::class, 'domainAnalytics',
            ])->name('domain-analytics.index');

            // Alerts
            Route::get('/alerts', function () {
                return inertia('Admin/Alerts');
            })->name('alerts.index');

            // Question Bank Management
            Route::prefix('questions')->name('questions.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\QuestionController::class, 'index'])->name('index');
                Route::get('/create', [\App\Http\Controllers\Admin\QuestionController::class, 'create'])->name('create');
                Route::post('/', [\App\Http\Controllers\Admin\QuestionController::class, 'store'])->name('store');
                Route::get('/{question}', [\App\Http\Controllers\Admin\QuestionController::class, 'edit'])->name('edit');
                Route::put('/{question}', [\App\Http\Controllers\Admin\QuestionController::class, 'update'])->name('update');
                Route::delete('/{question}', [\App\Http\Controllers\Admin\QuestionController::class, 'destroy'])->name('destroy');

                // Variant management
                Route::post('/{question}/variants', [\App\Http\Controllers\Admin\QuestionVariantController::class, 'store'])->name('variants.store');
                Route::put('/{question}/variants/{variant}', [\App\Http\Controllers\Admin\QuestionVariantController::class, 'update'])->name('variants.update');
                Route::delete('/{question}/variants/{variant}', [\App\Http\Controllers\Admin\QuestionVariantController::class, 'destroy'])->name('variants.destroy');

                // Markdown regeneration
                Route::post('/regenerate-markdown', [\App\Http\Controllers\Admin\QuestionController::class, 'regenerateMarkdown'])->name('regenerate-markdown');
            });
        });

        // Workflow management system (admin only)
        Route::middleware(['auth', 'admin'])->prefix('admin/workflows')->name('workflows.')->group(function () {
            // Workflow index page
            Route::get('/', [\App\Http\Controllers\ReferenceDocumentsController::class, 'workflowIndex'])
                ->name('index');

            // Reference documents management
            Route::get('/docs', [\App\Http\Controllers\ReferenceDocumentsController::class, 'index'])
                ->name('docs.index');

            Route::get('/docs/api/list', [\App\Http\Controllers\ReferenceDocumentsController::class, 'list'])
                ->name('docs.list');

            Route::get('/docs/api/{type}/{filename}',
                [\App\Http\Controllers\ReferenceDocumentsController::class, 'show'])
                ->name('docs.show')
                ->where(['type' => 'core|framework', 'filename' => '[^/]+']);

            Route::post('/docs/api/{type}/{filename}',
                [\App\Http\Controllers\ReferenceDocumentsController::class, 'update'])
                ->name('docs.update')
                ->where(['type' => 'core|framework', 'filename' => '[^/]+']);

            Route::post('/docs/api/embed-all',
                [\App\Http\Controllers\ReferenceDocumentsController::class, 'embedAll'])
                ->name('docs.embed-all');

            // Debug n8n workflow
            Route::get('/{workflowNumber}', [\App\Http\Controllers\DebugN8nController::class, 'show'])
                ->name('show')
                ->where('workflowNumber', '[0-9]+');

            // Set variant preference for workflow
            Route::post('/{workflowNumber}/variant',
                [\App\Http\Controllers\DebugN8nController::class, 'setVariant'])
                ->name('set-variant')
                ->where('workflowNumber', '[0-9]+');

            // Debug API endpoints
            Route::post('/{workflowNumber}/input',
                [\App\Http\Controllers\DebugN8nController::class, 'saveInput'])
                ->name('save-input')
                ->where('workflowNumber', '[0-9]+');

            Route::post('/{workflowNumber}/pass-input/{passNumber}',
                [\App\Http\Controllers\DebugN8nController::class, 'savePassInput'])
                ->name('save-pass-input')
                ->where('workflowNumber', '[0-9]+')
                ->where('passNumber', '[0-9]+');

            Route::post('/{workflowNumber}/javascript-old',
                [\App\Http\Controllers\DebugN8nController::class, 'saveOldJavaScript'])
                ->name('save-javascript-old')
                ->where('workflowNumber', '[0-9]+');

            Route::post('/{workflowNumber}/javascript-new',
                [\App\Http\Controllers\DebugN8nController::class, 'saveNewJavaScript'])
                ->name('save-javascript-new')
                ->where('workflowNumber', '[0-9]+');

            Route::post('/{workflowNumber}/reload-javascript-old',
                [\App\Http\Controllers\DebugN8nController::class, 'reloadJavaScriptFromWorkflow'])
                ->name('reload-javascript-old')
                ->where('workflowNumber', '[0-9]+');

            Route::post('/{workflowNumber}/reload-javascript-new',
                [\App\Http\Controllers\DebugN8nController::class, 'reloadJavaScriptFromWorkflowAsNew'])
                ->name('reload-javascript-new')
                ->where('workflowNumber', '[0-9]+');

            Route::post('/{workflowNumber}/prepare-prompt-old',
                [\App\Http\Controllers\DebugN8nController::class, 'preparePromptOld'])
                ->name('prepare-prompt-old')
                ->where('workflowNumber', '[0-9]+');

            Route::post('/{workflowNumber}/prepare-prompt-new',
                [\App\Http\Controllers\DebugN8nController::class, 'preparePromptNew'])
                ->name('prepare-prompt-new')
                ->where('workflowNumber', '[0-9]+');

            Route::post('/{workflowNumber}/save-to-n8n',
                [\App\Http\Controllers\DebugN8nController::class, 'saveJavaScriptToN8nWorkflow'])
                ->name('save-to-n8n')
                ->where('workflowNumber', '[0-9]+');

            Route::post('/{workflowNumber}/upload-to-n8n-old',
                [\App\Http\Controllers\DebugN8nController::class, 'uploadOldWorkflowToN8n'])
                ->name('upload-to-n8n-old')
                ->where('workflowNumber', '[0-9]+');

            Route::post('/{workflowNumber}/upload-to-n8n-new',
                [\App\Http\Controllers\DebugN8nController::class, 'uploadNewWorkflowToN8n'])
                ->name('upload-to-n8n-new')
                ->where('workflowNumber', '[0-9]+');

            Route::post('/{workflowNumber}/execute-workflow-old',
                [\App\Http\Controllers\DebugN8nController::class, 'executeOldWorkflow'])
                ->name('execute-workflow-old')
                ->where('workflowNumber', '[0-9]+');

            Route::post('/{workflowNumber}/execute-workflow-new',
                [\App\Http\Controllers\DebugN8nController::class, 'executeNewWorkflow'])
                ->name('execute-workflow-new')
                ->where('workflowNumber', '[0-9]+');

            Route::post('/{workflowNumber}/upload-to-live',
                [\App\Http\Controllers\DebugN8nController::class, 'uploadWorkflowToLive'])
                ->name('upload-to-live')
                ->where('workflowNumber', '[0-9]+');
        });
    });

// Auth routes (no locale prefix needed - handled by auth middleware)
require __DIR__.'/auth.php';

// E2E Test-Only Routes (no locale prefix)
Route::post('/test/login', function (Illuminate\Http\Request $request) {
    if ($request->header('X-Test-Auth') !== 'playwright-e2e-tests') {
        abort(403, 'Unauthorized');
    }

    $user = \App\Models\User::where('email', $request->email)->first();

    if (! $user) {
        $user = \App\Models\User::create([
            'email' => $request->email,
            'name' => $request->input('name', 'Test User'),
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
        ]);
    }

    \Illuminate\Support\Facades\Auth::login($user);

    return response()->json(['success' => true]);
})->name('test.login');

Route::post('/test/oauth-login', function (Illuminate\Http\Request $request) {
    if ($request->header('X-Test-Auth') !== 'playwright-e2e-tests') {
        abort(403, 'Unauthorized');
    }

    $email = $request->input('email', 'oauth-test@example.com');
    $name = $request->input('name', 'OAuth Test User');
    $googleId = $request->input('google_id', 'test-google-id-'.uniqid());

    $user = \App\Models\User::where('google_id', $googleId)
        ->orWhere('email', $email)
        ->first();

    if (! $user) {
        $user = \App\Models\User::create([
            'name' => $name,
            'email' => $email,
            'google_id' => $googleId,
            'password' => bcrypt('oauth-'.$googleId),
        ]);
    } else {
        $user->update([
            'google_id' => $googleId,
            'name' => $name,
        ]);
    }

    \Illuminate\Support\Facades\Auth::login($user);

    return response()->json(['success' => true, 'user_id' => $user->id]);
})->name('test.oauth-login');

// E2E Test-Only Broadcast Routes
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

// Mock n8n webhook endpoints for E2E testing (only in e2e environment)
if (config('app.env') === 'e2e') {
    Route::post('/webhook/api/n8n/webhook/pre-analysis', [MockN8nController::class, 'workflow0'])
        ->name('test.n8n.workflow0');
    Route::post('/webhook/api/n8n/webhook/analysis', [MockN8nController::class, 'workflow1'])
        ->name('test.n8n.workflow1');
    Route::post('/webhook/api/n8n/webhook/generate', [MockN8nController::class, 'workflow2'])
        ->name('test.n8n.workflow2');
}
