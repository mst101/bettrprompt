<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class FeedbackController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Feedback/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'experienceLevel' => ['required', 'integer', 'min:1', 'max:7'],
            'usefulness' => ['required', 'integer', 'min:1', 'max:7'],
            'suggestions' => ['nullable', 'string', 'max:5000'],
        ]);

        DB::table('feedback')->insert([
            'user_id' => auth()->id(),
            'experience_level' => $validated['experienceLevel'],
            'usefulness' => $validated['usefulness'],
            'suggestions' => $validated['suggestions'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('prompt-optimizer.history')
            ->with('success', 'Thank you for your feedback!');
    }
}
