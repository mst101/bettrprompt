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

    /**
     * Get personality calibration optimized for specific types
     * Returns core calibration + trait details only for mentioned personality types
     */
    public function personalityCalibrationSmart(string $types = ''): JsonResponse
    {
        $cacheKey = $types ? "personality_calibration_smart_{$types}" : 'personality_calibration_smart_core_only';

        $data = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($types) {
            $disk = Storage::disk('reference_documents');

            if (! $disk->exists('personality_calibration_core.md')) {
                return null;
            }

            $content = $disk->get('personality_calibration_core.md');

            // If specific types requested and traits file exists, add relevant trait details
            if ($types && $disk->exists('personality_traits_detailed.md')) {
                $traitsContent = $disk->get('personality_traits_detailed.md');
                $content .= "\n\n---\n\n## Relevant Trait Influence Matrices\n\n";
                $content .= $traitsContent;
            }

            return [
                'content' => $content,
                'types_included' => $types ?: 'core_only',
                'last_updated' => $disk->lastModified('personality_calibration_core.md'),
            ];
        });

        if ($data === null) {
            return response()->json([
                'success' => false,
                'error' => 'Personality calibration document not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'content' => $data['content'],
            'types_included' => $data['types_included'],
            'last_updated' => date('c', $data['last_updated']),
        ]);
    }

    public function questionBank(): JsonResponse
    {
        return $this->getReference('question_bank.md');
    }

    public function promptTemplates(): JsonResponse
    {
        return $this->getReference('prompt_templates.md');
    }

    /**
     * Get a specific framework template by code
     */
    public function frameworkTemplate(string $code): JsonResponse
    {
        $cacheKey = "framework_template_{$code}";

        $data = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($code) {
            $disk = Storage::disk('reference_documents');
            $templatePath = "framework_templates/{$code}.md";

            if (! $disk->exists($templatePath)) {
                return null;
            }

            return [
                'content' => $disk->get($templatePath),
                'last_updated' => $disk->lastModified($templatePath),
            ];
        });

        if ($data === null) {
            return response()->json([
                'success' => false,
                'error' => "Framework template not found: {$code}",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'content' => $data['content'],
            'framework_code' => $code,
            'last_updated' => date('c', $data['last_updated']),
        ]);
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
