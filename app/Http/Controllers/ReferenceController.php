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

            if (! $disk->exists('prompt_templates.md')) {
                return null;
            }

            $content = $disk->get('prompt_templates.md');
            $template = $this->extractFrameworkTemplate($content, $code);

            if ($template === null) {
                return null;
            }

            return [
                'content' => $template,
                'last_updated' => $disk->lastModified('prompt_templates.md'),
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

    /**
     * Extract a specific framework template from the prompt_templates.md content
     */
    private function extractFrameworkTemplate(string $content, string $code): ?string
    {
        // Map framework codes to their template section names
        $frameworkNames = [
            'COAST' => 'COAST Framework Template',
            'BAB' => 'BAB (Before-After-Bridge) Framework Template',
            'CHAIN_OF_THOUGHT' => 'Chain of Thought Framework Template',
            'SCAMPER' => 'SCAMPER Framework Template',
            'RISE' => 'RISE Framework Template (Feedback)',
            'CUSTOMER_COMMUNICATION' => 'Customer Communication Template (Content Creation)',
        ];

        if (! isset($frameworkNames[$code])) {
            // If code not found in map, try to find it directly
            $searchPattern = "### {$code} ";
        } else {
            $searchPattern = "### {$frameworkNames[$code]}";
        }

        // Find the start of this framework template
        $startPos = strpos($content, $searchPattern);
        if ($startPos === false) {
            return null;
        }

        // Find the next ### heading (start of next template)
        $nextHeadingPos = strpos($content, "\n### ", $startPos + 1);

        // If no next heading found, take until the end
        if ($nextHeadingPos === false) {
            $template = substr($content, $startPos);
        } else {
            $template = substr($content, $startPos, $nextHeadingPos - $startPos);
        }

        // Also include the general guidance sections at the top
        $guidanceEnd = strpos($content, '## Framework Templates');
        if ($guidanceEnd === false) {
            $guidanceEnd = strpos($content, '## Applying Task-Trait Alignment');
        }

        if ($guidanceEnd !== false) {
            $guidance = substr($content, 0, $guidanceEnd);
            $template = $guidance."\n\n".$template;
        }

        return trim($template);
    }
}
