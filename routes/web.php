<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

//Route::get('/dashboard', function () {
//    return Inertia::render('Dashboard');
//})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/dashboard', [\App\Http\Controllers\PromptOptimizerController::class, 'index'])
    ->name('dashboard');


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Prompt Optimizer routes
    Route::get('/prompt-optimizer', [\App\Http\Controllers\PromptOptimizerController::class, 'index'])
        ->name('prompt-optimizer.index');
    Route::post('/prompt-optimizer', [\App\Http\Controllers\PromptOptimizerController::class, 'store'])
        ->name('prompt-optimizer.store');
    Route::get('/prompt-optimizer/{promptRun}', [\App\Http\Controllers\PromptOptimizerController::class, 'show'])
        ->name('prompt-optimizer.show');
    Route::get('/prompt-optimizer-history', [\App\Http\Controllers\PromptOptimizerController::class, 'history'])
        ->name('prompt-optimizer.history');
});

require __DIR__.'/auth.php';
