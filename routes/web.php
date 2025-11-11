<?php

use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\ProfileController;
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

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/personality',
        [ProfileController::class, 'updatePersonality'])->name('profile.personality.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Prompt Optimizer routes
    Route::get('/prompt-optimizer', [\App\Http\Controllers\PromptOptimizerController::class, 'index'])
        ->name('prompt-optimizer.index');
    Route::post('/prompt-optimizer', [\App\Http\Controllers\PromptOptimizerController::class, 'store'])
        ->name('prompt-optimizer.store');
    Route::get('/prompt-optimizer/{promptRun}', [\App\Http\Controllers\PromptOptimizerController::class, 'show'])
        ->name('prompt-optimizer.show');
    Route::post('/prompt-optimizer/{promptRun}/answer',
        [\App\Http\Controllers\PromptOptimizerController::class, 'answerQuestion'])
        ->name('prompt-optimizer.answer');
    Route::post('/prompt-optimizer/{promptRun}/skip',
        [\App\Http\Controllers\PromptOptimizerController::class, 'skipQuestion'])
        ->name('prompt-optimizer.skip');
    Route::post('/prompt-optimizer/{promptRun}/retry',
        [\App\Http\Controllers\PromptOptimizerController::class, 'retry'])
        ->name('prompt-optimizer.retry');
    Route::post('/prompt-optimizer/{parentPromptRun}/create-child',
        [\App\Http\Controllers\PromptOptimizerController::class, 'createChild'])
        ->name('prompt-optimizer.create-child');
    Route::post('/prompt-optimizer/{parentPromptRun}/create-child-from-answers',
        [\App\Http\Controllers\PromptOptimizerController::class, 'createChildFromAnswers'])
        ->name('prompt-optimizer.create-child-from-answers');
    Route::get('/prompt-optimizer-history', [\App\Http\Controllers\PromptOptimizerController::class, 'history'])
        ->name('prompt-optimizer.history');

    // Feedback routes
    Route::get('/feedback/create', [FeedbackController::class, 'create'])
        ->name('feedback.create');
    Route::get('/feedback', [FeedbackController::class, 'show'])
        ->name('feedback.show');
    Route::post('/feedback', [FeedbackController::class, 'store'])
        ->name('feedback.store');
    Route::put('/feedback', [FeedbackController::class, 'update'])
        ->name('feedback.update');

    // Voice transcription endpoint (requires session authentication)
    Route::post('/voice-transcription', [VoiceTranscriptionController::class, 'transcribe'])
        ->middleware('throttle:30,1');
});

require __DIR__.'/auth.php';
