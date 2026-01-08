<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClaudeModelResource;
use App\Http\Resources\PromptRunResource;
use App\Models\ClaudeModel;
use App\Models\PromptRun;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    /**
     * Display a listing of tasks (unique task descriptions)
     */
    public function index(Request $request): Response
    {
        $tasksQuery = PromptRun::select('task_description', DB::raw('COUNT(*) as runs_count'))
            ->whereNotNull('task_description')
            ->groupBy('task_description')
            ->orderByDesc('runs_count');

        if ($request->search) {
            $tasksQuery->where('task_description', 'like', "%$request->search%");
        }

        $tasks = $tasksQuery->paginate(20)->withQueryString();

        // Add a sequential task_id to each task for URL routing
        $tasks->getCollection()->transform(function ($task, $index) use ($tasks) {
            $task->task_id = (($tasks->currentPage() - 1) * $tasks->perPage()) + $index + 1;

            return $task;
        });

        return Inertia::render('Admin/Tasks/Index', [
            'tasks' => $tasks,
            'filters' => $request->only('search'),
        ]);
    }

    /**
     * Display prompt runs for a specific task
     */
    public function show(string $locale, int $taskId): Response
    {
        // Get all unique tasks ordered by runs count (same as index)
        $tasks = PromptRun::select('task_description', DB::raw('COUNT(*) as runs_count'))
            ->whereNotNull('task_description')
            ->groupBy('task_description')
            ->orderByDesc('runs_count')
            ->get();

        // Get the task at the specified index (taskId is 1-based)
        $taskIndex = $taskId - 1;

        if ($taskIndex < 0 || $taskIndex >= $tasks->count()) {
            abort(404, __('messages.admin.task_not_found'));
        }

        $taskDescription = $tasks[$taskIndex]->task_description;

        $promptRuns = PromptRun::with(['user', 'visitor'])
            ->where('task_description', $taskDescription)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Tasks/Show', [
            'taskDescription' => $taskDescription,
            'promptRuns' => PromptRunResource::collection($promptRuns)->resolve(),
        ]);
    }

    /**
     * Display details of a specific prompt run
     */
    public function promptRun(string $locale, PromptRun $promptRun): Response
    {
        $promptRun->load(['user', 'visitor', 'parent', 'children']);

        return Inertia::render('Admin/PromptRuns/Show', [
            'promptRun' => PromptRunResource::make($promptRun)->resolve(),
            'claudeModels' => ClaudeModelResource::collection(
                ClaudeModel::active()->orderByDesc('release_date')->get()
            )->resolve(),
        ]);
    }
}
