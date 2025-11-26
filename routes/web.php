<?php

use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PromptBuilderController;
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

Route::get('/dashboard', [\App\Http\Controllers\PromptOptimizerController::class, 'index'])
    ->name('dashboard');

// Prompt Optimizer routes (no authentication required)
Route::get('/prompt-optimizer', [\App\Http\Controllers\PromptOptimizerController::class, 'index'])
    ->name('prompt-optimizer.index');
Route::post('/prompt-optimizer', [\App\Http\Controllers\PromptOptimizerController::class, 'store'])
    ->name('prompt-optimizer.store');
Route::patch('/visitor/personality',
    [\App\Http\Controllers\PromptOptimizerController::class, 'updateVisitorPersonality'])
    ->name('visitor.personality.update');
Route::get('/prompt-optimizer/{promptRun}', [\App\Http\Controllers\PromptOptimizerController::class, 'show'])
    ->name('prompt-optimizer.show');
Route::post('/prompt-optimizer/{promptRun}/answer',
    [\App\Http\Controllers\PromptOptimizerController::class, 'answerQuestion'])
    ->name('prompt-optimizer.answer');
Route::post('/prompt-optimizer/{promptRun}/submit-all-answers',
    [\App\Http\Controllers\PromptOptimizerController::class, 'submitAllAnswers'])
    ->name('prompt-optimizer.submit-all-answers');
Route::post('/prompt-optimizer/{promptRun}/skip',
    [\App\Http\Controllers\PromptOptimizerController::class, 'skipQuestion'])
    ->name('prompt-optimizer.skip');
Route::post('/prompt-optimizer/{promptRun}/go-back',
    [\App\Http\Controllers\PromptOptimizerController::class, 'goBackToPreviousQuestion'])
    ->name('prompt-optimizer.go-back');
Route::post('/prompt-optimizer/{promptRun}/retry',
    [\App\Http\Controllers\PromptOptimizerController::class, 'retry'])
    ->name('prompt-optimizer.retry');
Route::post('/prompt-optimizer/{parentPromptRun}/create-child',
    [\App\Http\Controllers\PromptOptimizerController::class, 'createChild'])
    ->name('prompt-optimizer.create-child');
Route::post('/prompt-optimizer/{parentPromptRun}/create-child-from-answers',
    [\App\Http\Controllers\PromptOptimizerController::class, 'createChildFromAnswers'])
    ->name('prompt-optimizer.create-child-from-answers');
Route::patch('/prompt-optimizer/{promptRun}/update-prompt',
    [\App\Http\Controllers\PromptOptimizerController::class, 'updateOptimizedPrompt'])
    ->name('prompt-optimizer.update-prompt');

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

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/personality',
        [ProfileController::class, 'updatePersonality'])->name('profile.personality.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Prompt Optimizer history (requires authentication)
    Route::get('/prompt-optimizer-history', [\App\Http\Controllers\PromptOptimizerController::class, 'history'])
        ->name('prompt-optimizer.history');
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

Route::middleware(['auth'])->group(function () {
    Route::get('/prompt-builder', [PromptBuilderController::class, 'index'])
        ->name('prompt-builder.index');
    Route::post('/prompt-builder/analyse', [PromptBuilderController::class, 'analyse'])
        ->name('prompt-builder.analyse');
    Route::get('/prompt-builder/questions', [PromptBuilderController::class, 'questions'])
        ->name('prompt-builder.questions');
    Route::post('/prompt-builder/generate', [PromptBuilderController::class, 'generate'])
        ->name('prompt-builder.generate');
});
