<?php

use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\ProfileController;
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
    Route::get('/prompt-optimizer-history', [\App\Http\Controllers\PromptOptimizerController::class, 'history'])
        ->name('prompt-optimizer.history');
});

require __DIR__.'/auth.php';
