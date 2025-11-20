<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PromptRun;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    /**
     * Display the admin dashboard
     */
    public function index(): Response
    {
        $stats = [
            'total_users' => User::count(),
            'total_prompt_runs' => PromptRun::count(),
            'unique_tasks' => PromptRun::distinct('task_description')->count('task_description'),
            'completed_runs' => PromptRun::where('status', 'completed')->count(),
        ];

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
        ]);
    }
}
