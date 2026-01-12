<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Experiment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExperimentsController extends Controller
{
    /**
     * Display a listing of experiments
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Experiment::class);

        $experiments = Experiment::query()
            ->orderBy('started_at', 'desc')
            ->paginate(50);

        return Inertia::render('Admin/Experiments/Index', [
            'experiments' => $experiments,
        ]);
    }

    /**
     * Show the form for creating a new experiment
     */
    public function create(): Response
    {
        $this->authorize('create', Experiment::class);

        return Inertia::render('Admin/Experiments/Create');
    }

    /**
     * Store a newly created experiment in storage
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Experiment::class);

        $validated = $request->validate([
            'slug' => 'required|string|max:100|unique:experiments',
            'name' => 'required|string|max:200',
            'description' => 'nullable|string',
            'hypothesis' => 'nullable|string',
            'goal_event' => 'required|string|max:100',
            'goal_type' => 'required|in:conversion,revenue,engagement',
            'traffic_percentage' => 'required|integer|min:1|max:100',
            'minimum_sample_size' => 'nullable|integer|min:10',
            'minimum_detectable_effect' => 'nullable|numeric|min:0.01|max:100',
            'minimum_runtime_hours' => 'nullable|integer|min:1',
        ]);

        $experiment = Experiment::create([
            ...$validated,
            'status' => 'draft',
        ]);

        return redirect()
            ->route('admin.experiments.show', $experiment)
            ->with('success', 'Experiment created successfully');
    }

    /**
     * Display the specified experiment
     */
    public function show(Experiment $experiment): Response
    {
        $this->authorize('view', $experiment);

        $experiment->load('variants', 'assignments', 'exposures');

        return Inertia::render('Admin/Experiments/Show', [
            'experiment' => $experiment,
        ]);
    }

    /**
     * Show the form for editing the experiment
     */
    public function edit(Experiment $experiment): Response
    {
        $this->authorize('update', $experiment);

        return Inertia::render('Admin/Experiments/Edit', [
            'experiment' => $experiment,
        ]);
    }

    /**
     * Update the specified experiment in storage
     */
    public function update(Request $request, Experiment $experiment): RedirectResponse
    {
        $this->authorize('update', $experiment);

        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'description' => 'nullable|string',
            'hypothesis' => 'nullable|string',
            'goal_event' => 'required|string|max:100',
            'goal_type' => 'required|in:conversion,revenue,engagement',
            'traffic_percentage' => 'required|integer|min:1|max:100',
            'minimum_sample_size' => 'nullable|integer|min:10',
            'minimum_detectable_effect' => 'nullable|numeric|min:0.01|max:100',
            'minimum_runtime_hours' => 'nullable|integer|min:1',
        ]);

        $experiment->update($validated);

        return redirect()
            ->route('admin.experiments.show', $experiment)
            ->with('success', 'Experiment updated successfully');
    }

    /**
     * Launch an experiment (change status from draft to running)
     */
    public function launch(Experiment $experiment): RedirectResponse
    {
        $this->authorize('update', $experiment);

        if ($experiment->status !== 'draft') {
            return redirect()
                ->back()
                ->with('error', 'Only draft experiments can be launched');
        }

        // Validate variants exist
        if ($experiment->variants()->count() === 0) {
            return redirect()
                ->back()
                ->with('error', 'Experiment must have at least one variant');
        }

        $experiment->update([
            'status' => 'running',
            'started_at' => now(),
        ]);

        return redirect()
            ->route('admin.experiments.show', $experiment)
            ->with('success', 'Experiment launched successfully');
    }

    /**
     * Pause an experiment
     */
    public function pause(Experiment $experiment): RedirectResponse
    {
        $this->authorize('update', $experiment);

        if ($experiment->status !== 'running') {
            return redirect()
                ->back()
                ->with('error', 'Only running experiments can be paused');
        }

        $experiment->update(['status' => 'paused']);

        return redirect()
            ->route('admin.experiments.show', $experiment)
            ->with('success', 'Experiment paused');
    }

    /**
     * Resume an experiment
     */
    public function resume(Experiment $experiment): RedirectResponse
    {
        $this->authorize('update', $experiment);

        if ($experiment->status !== 'paused') {
            return redirect()
                ->back()
                ->with('error', 'Only paused experiments can be resumed');
        }

        $experiment->update(['status' => 'running']);

        return redirect()
            ->route('admin.experiments.show', $experiment)
            ->with('success', 'Experiment resumed');
    }

    /**
     * Complete an experiment
     */
    public function complete(Experiment $experiment): RedirectResponse
    {
        $this->authorize('update', $experiment);

        if (! in_array($experiment->status, ['running', 'paused'])) {
            return redirect()
                ->back()
                ->with('error', 'Only active experiments can be completed');
        }

        $experiment->update([
            'status' => 'completed',
            'ended_at' => now(),
        ]);

        return redirect()
            ->route('admin.experiments.show', $experiment)
            ->with('success', 'Experiment completed');
    }

    /**
     * Delete the specified experiment
     */
    public function destroy(Experiment $experiment): RedirectResponse
    {
        $this->authorize('delete', $experiment);

        if ($experiment->status !== 'draft') {
            return redirect()
                ->back()
                ->with('error', 'Only draft experiments can be deleted');
        }

        $experiment->delete();

        return redirect()
            ->route('admin.experiments.index')
            ->with('success', 'Experiment deleted successfully');
    }
}
