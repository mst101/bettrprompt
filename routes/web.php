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

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/personality',
        [ProfileController::class, 'updatePersonality'])->name('profile.personality.update');
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
Route::delete('/prompt-builder/{promptRun}', [PromptBuilderController::class, 'destroy'])
    ->name('prompt-builder.destroy');
Route::post('/prompt-builder/{promptRun}/answer', [PromptBuilderController::class, 'answerQuestion'])
    ->name('prompt-builder.answer');
Route::post('/prompt-builder/{promptRun}/skip', [PromptBuilderController::class, 'skipQuestion'])
    ->name('prompt-builder.skip');
Route::patch('/prompt-builder/{promptRun}/update-prompt', [PromptBuilderController::class, 'updateOptimizedPrompt'])
    ->name('prompt-builder.update-prompt');
// });
