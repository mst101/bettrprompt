<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display a listing of users
     */
    public function index(Request $request): Response
    {
        $users = User::query()
            ->withCount([
                'visitors',
                'visitors as prompt_runs_count' => function ($query) {
                    $query->join('prompt_runs', 'visitors.id', '=', 'prompt_runs.visitor_id');
                },
            ])
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $request->only('search'),
        ]);
    }

    /**
     * Display the specified user
     */
    public function show(User $user): Response
    {
        $user->load([
            'visitors.promptRuns' => function ($query) {
                $query->latest()->limit(10);
            },
        ]);

        $promptRunsCount = $user->visitors()
            ->with('promptRuns')
            ->get()
            ->pluck('promptRuns')
            ->flatten()
            ->count();

        return Inertia::render('Admin/Users/Show', [
            'user' => new UserResource($user),
            'promptRunsCount' => $promptRunsCount,
        ]);
    }
}
