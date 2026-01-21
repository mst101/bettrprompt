<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreQuestionVariantRequest;
use App\Http\Requests\Admin\UpdateQuestionVariantRequest;
use App\Http\Resources\QuestionVariantResource;
use App\Models\Question;
use App\Models\QuestionVariant;
use Illuminate\Http\JsonResponse;

class QuestionVariantController extends Controller
{
    /**
     * Store a newly created variant in storage.
     */
    public function store(StoreQuestionVariantRequest $request, Question $question): JsonResponse
    {
        $variant = $question->variants()->create($request->validated());

        return response()->json(
            new QuestionVariantResource($variant),
            201
        );
    }

    /**
     * Update the specified variant in storage.
     */
    public function update(UpdateQuestionVariantRequest $request, Question $question, QuestionVariant $variant): JsonResponse
    {
        $variant->update($request->validated());

        return response()->json(
            new QuestionVariantResource($variant)
        );
    }

    /**
     * Remove the specified variant from storage.
     */
    public function destroy(Question $question, QuestionVariant $variant): JsonResponse
    {
        $variant->delete();

        return response()->json(null, 204);
    }
}
