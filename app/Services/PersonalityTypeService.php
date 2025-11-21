<?php

namespace App\Services;

class PersonalityTypeService
{
    /**
     * Map MBTI codes to folder names
     */
    private const TYPE_MAPPING = [
        'INTJ' => 'Architect',
        'INTP' => 'Logician',
        'ENTJ' => 'Commander',
        'ENTP' => 'Debater',
        'INFJ' => 'Advocate',
        'INFP' => 'Mediator',
        'ENFJ' => 'Protagonist',
        'ENFP' => 'Campaigner',
        'ISTJ' => 'Logistician',
        'ISFJ' => 'Defender',
        'ESTJ' => 'Executive',
        'ESFJ' => 'Consul',
        'ISTP' => 'Virtuoso',
        'ISFP' => 'Adventurer',
        'ESTP' => 'Entrepreneur',
        'ESFP' => 'Entertainer',
    ];

    /**
     * Get the folder name for a personality type code
     */
    public static function getFolderName(?string $typeCode): ?string
    {
        if (! $typeCode) {
            return null;
        }

        // Remove -A or -T suffix if present
        $cleanCode = preg_replace('/-[AT]$/', '', $typeCode);

        return self::TYPE_MAPPING[$cleanCode] ?? null;
    }

    /**
     * Get available PDF files for a personality type
     */
    public static function getAvailablePdfs(?string $typeCode): array
    {
        $folderName = self::getFolderName($typeCode);

        if (! $folderName) {
            return [];
        }

        $pdfPath = resource_path("pdf/{$folderName}");

        if (! is_dir($pdfPath)) {
            return [];
        }

        $files = scandir($pdfPath);
        $pdfs = [];

        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'pdf') {
                $pdfs[] = [
                    'name' => pathinfo($file, PATHINFO_FILENAME),
                    'filename' => $file,
                ];
            }
        }

        return $pdfs;
    }

    /**
     * Get all personality types mapping
     */
    public static function getAllTypes(): array
    {
        return self::TYPE_MAPPING;
    }
}
