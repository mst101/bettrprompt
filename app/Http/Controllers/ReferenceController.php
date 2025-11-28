<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ReferenceController extends Controller
{
    /**
     * Cache duration in seconds (1 hour)
     */
    private const CACHE_DURATION = 3600;

    public function frameworkTaxonomy(): JsonResponse
    {
        return $this->getReference('framework_taxonomy_compressed.md');
    }

    public function personalityCalibration(): JsonResponse
    {
        return $this->getReference('personality_calibration.md');
    }

    public function questionBank(): JsonResponse
    {
        return $this->getReference('question_bank.md');
    }

    public function promptTemplates(): JsonResponse
    {
        return $this->getReference('prompt_templates.md');
    }

    private function getReference(string $filename): JsonResponse
    {
        $cacheKey = "reference_doc_{$filename}";

        $data = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($filename) {
            $disk = Storage::disk('reference_documents');

            if (! $disk->exists($filename)) {
                return null;
            }

            return [
                'content' => $disk->get($filename),
                'last_updated' => $disk->lastModified($filename),
            ];
        });

        if ($data === null) {
            return response()->json([
                'success' => false,
                'error' => "Reference document not found: {$filename}",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'content' => $data['content'],
            'last_updated' => date('c', $data['last_updated']),
        ]);
    }
}
