<?php

use App\Services\RecoveryPhraseService;

describe('Recovery Phrase Generation', function () {
    test('generates 12-word recovery phrase by default', function () {
        $service = new RecoveryPhraseService;
        $phrase = $service->generate();

        $words = explode(' ', $phrase);
        expect($words)->toHaveCount(12);
    });

    test('generates phrase with custom word count', function () {
        $service = new RecoveryPhraseService;

        $phrase5 = $service->generate(5);
        expect(explode(' ', $phrase5))->toHaveCount(5);

        $phrase20 = $service->generate(20);
        expect(explode(' ', $phrase20))->toHaveCount(20);
    });

    test('all generated words are from valid word list', function () {
        $service = new RecoveryPhraseService;
        $wordList = $service->getWordList();

        for ($i = 0; $i < 10; $i++) {
            $phrase = $service->generate();
            $words = explode(' ', $phrase);

            foreach ($words as $word) {
                expect(in_array($word, $wordList, true))->toBeTrue();
            }
        }
    });

    test('generates different phrases on each call', function () {
        $service = new RecoveryPhraseService;

        $phrase1 = $service->generate();
        $phrase2 = $service->generate();
        $phrase3 = $service->generate();

        expect($phrase1)->not->toBe($phrase2);
        expect($phrase2)->not->toBe($phrase3);
        expect($phrase1)->not->toBe($phrase3);
    });

    test('word list contains only lowercase words', function () {
        $service = new RecoveryPhraseService;
        $wordList = $service->getWordList();

        foreach ($wordList as $word) {
            expect($word)->toBe(strtolower($word));
        }
    });

    test('word list has no duplicates', function () {
        $service = new RecoveryPhraseService;
        $wordList = $service->getWordList();

        expect(count($wordList))->toBe(count(array_unique($wordList)));
    });
});

describe('Recovery Phrase Validation', function () {
    test('validates correct recovery phrase', function () {
        $service = new RecoveryPhraseService;

        // Generate a valid phrase and verify it validates
        $phrase = $service->generate();
        expect($service->validate($phrase))->toBeTrue();
    });

    test('rejects phrase with too few words', function () {
        $service = new RecoveryPhraseService;

        $tooFew = 'abandon ability able about above absent absorb abstract absurd abuse access'; // 11 words
        expect($service->validate($tooFew))->toBeFalse();
    });

    test('rejects phrase with too many words', function () {
        $service = new RecoveryPhraseService;

        $tooMany = 'abandon ability able about above absent absorb abstract absurd abuse access accident account acc'; // 14 words
        expect($service->validate($tooMany))->toBeFalse();
    });

    test('rejects phrase with invalid words', function () {
        $service = new RecoveryPhraseService;

        $invalid = 'abandon ability able about above absent absorb abstract absurd abuse access xyz notword invalid invalid extra'; // 15 words, some invalid
        expect($service->validate($invalid))->toBeFalse();
    });

    test('validates phrases with different spacing', function () {
        $service = new RecoveryPhraseService;

        // Generate valid phrase
        $phrase = $service->generate();

        // Extra spaces need to be normalised before validation
        $withExtraSpaces = preg_replace('/\s+/', '  ', $phrase);
        $normalised = $service->normalise($withExtraSpaces);
        expect($service->validate($normalised))->toBeTrue();
    });

    test('validates phrases regardless of case', function () {
        $service = new RecoveryPhraseService;

        // Generate valid phrase
        $phrase = $service->generate();

        // Uppercase should validate
        $uppercase = strtoupper($phrase);
        expect($service->validate($uppercase))->toBeTrue();

        // Mixed case should validate
        $mixed = ucwords($phrase);
        expect($service->validate($mixed))->toBeTrue();
    });

    test('rejects empty string', function () {
        $service = new RecoveryPhraseService;

        expect($service->validate(''))->toBeFalse();
    });

    test('rejects string with only whitespace', function () {
        $service = new RecoveryPhraseService;

        expect($service->validate('   '))->toBeFalse();
        expect($service->validate("\t\t"))->toBeFalse();
    });

    test('validates phrase with leading and trailing whitespace', function () {
        $service = new RecoveryPhraseService;

        $phrase = $service->generate();
        $withWhitespace = "  {$phrase}  ";

        expect($service->validate($withWhitespace))->toBeTrue();
    });
});

describe('Recovery Phrase Normalisation', function () {
    test('normalises to lowercase', function () {
        $service = new RecoveryPhraseService;

        $phrase = 'ABANDON ABILITY ABLE ABOUT ABOVE ABSENT ABSORB ABSTRACT ABSURD ABUSE ACCESS ACCIDENT';
        $normalised = $service->normalise($phrase);

        expect($normalised)->toBe('abandon ability able about above absent absorb abstract absurd abuse access accident');
    });

    test('trims whitespace', function () {
        $service = new RecoveryPhraseService;

        $phrase = '  abandon ability able about above absent absorb abstract absurd abuse access accident  ';
        $normalised = $service->normalise($phrase);

        expect($normalised)->toBe('abandon ability able about above absent absorb abstract absurd abuse access accident');
    });

    test('collapses multiple spaces into single space', function () {
        $service = new RecoveryPhraseService;

        $phrase = 'abandon  ability   able    about     above      absent       absorb        abstract         absurd          abuse           access            accident';
        $normalised = $service->normalise($phrase);

        expect($normalised)->toBe('abandon ability able about above absent absorb abstract absurd abuse access accident');
    });

    test('normalises tabs and newlines', function () {
        $service = new RecoveryPhraseService;

        $phrase = "abandon\tability\nable\nabout\nabove\tabsent\nabsorb\tabstract\nabsurd\tabuseaccess\taccident";
        $normalised = $service->normalise($phrase);

        // All whitespace should be collapsed to single space
        expect(preg_match('/\s{2,}/', $normalised))->toBe(0); // No multiple spaces
    });

    test('normalised phrases are consistent', function () {
        $service = new RecoveryPhraseService;

        $phrase = 'ABANDON ability ABLE about ABOVE absent ABSORB abstract ABSURD abuse ACCESS accident';
        $normalised1 = $service->normalise($phrase);
        $normalised2 = $service->normalise($phrase);

        expect($normalised1)->toBe($normalised2);
    });
});

describe('Word List Access', function () {
    test('word list is not empty', function () {
        $service = new RecoveryPhraseService;
        $wordList = $service->getWordList();

        expect($wordList)->not->toBeEmpty();
    });

    test('word list contains common English words', function () {
        $service = new RecoveryPhraseService;
        $wordList = $service->getWordList();

        // Check for some expected common words
        expect($wordList)->toContain('abandon');
        expect($wordList)->toContain('ability');
        expect($wordList)->toContain('about');
        expect($wordList)->toContain('book');
        expect($wordList)->toContain('dance');
    });

    test('all words in list are strings', function () {
        $service = new RecoveryPhraseService;
        $wordList = $service->getWordList();

        foreach ($wordList as $word) {
            expect($word)->toBeString();
        }
    });

    test('word list is consistent across multiple calls', function () {
        $service = new RecoveryPhraseService;

        $list1 = $service->getWordList();
        $list2 = $service->getWordList();

        expect($list1)->toBe($list2);
    });
});

describe('Integration Tests', function () {
    test('generates and validates recovery phrase', function () {
        $service = new RecoveryPhraseService;

        // Generate phrase
        $phrase = $service->generate();

        // Verify it validates
        expect($service->validate($phrase))->toBeTrue();
    });

    test('generates, normalises, and validates recovery phrase', function () {
        $service = new RecoveryPhraseService;

        // Generate phrase
        $phrase = $service->generate();

        // Normalise it (add spaces and uppercase)
        $modified = '  '.strtoupper($phrase).'  ';

        // Verify it still validates
        expect($service->validate($modified))->toBeTrue();

        // Verify normalised form matches
        $normalised = $service->normalise($phrase);
        expect($service->normalise($modified))->toBe($normalised);
    });

    test('recovery phrase flow matches typical user workflow', function () {
        $service = new RecoveryPhraseService;

        // User generates recovery phrase
        $phrase = $service->generate();

        // User writes it down with varied formatting
        $userInput = '  '.strtoupper(str_replace(' ', '  ', $phrase)).'  ';

        // System normalises user input first
        $normalised = $service->normalise($userInput);

        // Then validates the normalised phrase
        expect($service->validate($normalised))->toBeTrue();

        // System normalisation produces consistent output
        $stored = $service->normalise($userInput);
        expect($stored)->toBe($service->normalise($phrase));
    });
});
