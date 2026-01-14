<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreQuestionRequest;
use App\Http\Requests\Admin\UpdateQuestionRequest;
use App\Http\Resources\Admin\QuestionResource;
use App\Models\Question;
use App\Services\QuestionMarkdownGeneratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuestionController extends Controller
{
    /**
     * Display a listing of questions.
     */
    public function index(Request $request): Response
    {
        $questions = Question::with('variants')
            ->when($request->filled('category'), function ($query) use ($request) {
                return $query->where('category', $request->category);
            })
            ->when($request->filled('framework'), function ($query) use ($request) {
                return $query->where('framework', $request->framework);
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                return $query->where('question_text', 'ilike', '%'.$request->search.'%');
            })
            ->orderBy('category')
            ->orderBy('display_order')
            ->paginate(25);

        $categories = Question::distinct('category')->pluck('category')->sort();
        $frameworks = Question::whereNotNull('framework')->distinct('framework')->pluck('framework')->sort();

        return Inertia::render('Admin/Questions/Index', [
            'questions' => QuestionResource::collection($questions)->response()->getData(true),
            'categories' => $categories,
            'frameworks' => $frameworks,
            'filters' => $request->only(['category', 'framework', 'search']),
        ]);
    }

    /**
     * Show the form for creating a new question.
     */
    public function create(): Response
    {
        $categories = Question::distinct('category')->pluck('category')->sort();
        $frameworks = Question::whereNotNull('framework')->distinct('framework')->pluck('framework')->sort();

        return Inertia::render('Admin/Questions/Create', [
            'categories' => $categories,
            'frameworks' => $frameworks,
        ]);
    }

    /**
     * Store a newly created question in storage.
     */
    public function store(StoreQuestionRequest $request): RedirectResponse
    {
        Question::create($request->validated());

        return redirect()
            ->route('admin.questions.index')
            ->with('success', 'Question created successfully.');
    }

    /**
     * Show the form for editing the specified question.
     */
    public function edit(Question $question): Response
    {
        $question->load('variants');

        $categories = Question::distinct('category')->pluck('category')->sort();
        $frameworks = Question::whereNotNull('framework')->distinct('framework')->pluck('framework')->sort();
        $personalityPatterns = ['high_t_high_j', 'high_f_high_j', 'high_t_high_p', 'high_f_high_p', 'high_a', 'high_t_identity', 'high_s', 'high_n', 'high_t', 'high_f', 'high_j', 'high_p', 'neutral'];

        return Inertia::render('Admin/Questions/Edit', [
            'question' => new QuestionResource($question),
            'categories' => $categories,
            'frameworks' => $frameworks,
            'personalityPatterns' => $personalityPatterns,
        ]);
    }

    /**
     * Update the specified question in storage.
     */
    public function update(UpdateQuestionRequest $request, Question $question): RedirectResponse
    {
        $question->update($request->validated());

        return redirect()
            ->route('admin.questions.edit', $question)
            ->with('success', 'Question updated successfully.');
    }

    /**
     * Remove the specified question from storage (soft delete).
     */
    public function destroy(Question $question): RedirectResponse
    {
        $question->update(['is_active' => false]);

        return redirect()
            ->route('admin.questions.index')
            ->with('success', 'Question archived successfully.');
    }

    /**
     * Regenerate the markdown question bank file.
     */
    public function regenerateMarkdown(): RedirectResponse
    {
        try {
            $generator = app(QuestionMarkdownGeneratorService::class);
            $path = $generator->generateMarkdown();

            return redirect()
                ->route('admin.questions.index')
                ->with('success', "Question bank markdown regenerated successfully at $path");
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.questions.index')
                ->with('error', 'Failed to regenerate markdown: '.$e->getMessage());
        }
    }
}
