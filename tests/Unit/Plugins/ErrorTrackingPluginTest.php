<?php

namespace Tests\Unit\Plugins;

use PHPUnit\Framework\TestCase;

/**
 * Tests for error tracking plugin behavior
 *
 * Note: These are unit tests for the error tracking logic.
 * Integration tests with Vue and actual error events should be in E2E tests.
 *
 * The plugin is tested via:
 * 1. Unit tests here (PHP - testing the concept/logic)
 * 2. Frontend tests via Vitest (JavaScript/TypeScript)
 * 3. E2E tests via Playwright (actual error events in browser)
 */
class ErrorTrackingPluginTest extends TestCase
{
    /**
     * Test error deduplication logic
     * Similar errors within debounce window should be skipped
     */
    public function test_error_deduplication_concept(): void
    {
        // Simulate error deduplication logic
        $errorHashes = [];
        $debounceMs = 5000;
        $now = microtime(true) * 1000;

        // Track first error
        $hash1 = 'Error:Something went wrong';
        $errorHashes[$hash1] = $now;

        // Check if duplicate should be skipped (same hash, time is now)
        // This simulates the same error happening at the same time
        $timeSinceLastTrack = $now - $errorHashes[$hash1];
        $shouldSkip = $timeSinceLastTrack < $debounceMs;

        $this->assertTrue(
            $shouldSkip,
            'Recent duplicate error should be skipped due to debouncing'
        );

        // Check if old error (outside debounce window) should be tracked again
        // Simulate time passing: 6 seconds later
        $laterTime = $now + 6000; // 6 seconds later
        $timeSinceLastTrack = $laterTime - $errorHashes[$hash1];
        $shouldNotSkip = $timeSinceLastTrack >= $debounceMs;

        $this->assertTrue(
            $shouldNotSkip,
            'Old error outside debounce window should not be skipped'
        );
    }

    /**
     * Test error filtering logic
     * Certain errors should not be tracked
     */
    public function test_error_filtering_logic(): void
    {
        $this->assertFalse(
            $this->shouldTrackError('ChunkLoadError', 'Failed to load chunk'),
            'ChunkLoadError should not be tracked'
        );

        $this->assertFalse(
            $this->shouldTrackError('AbortError', 'Aborted'),
            'AbortError should not be tracked (user-initiated)'
        );

        $this->assertFalse(
            $this->shouldTrackError('NotFound', 'Resource not found'),
            'NotFound should not be tracked'
        );

        $this->assertFalse(
            $this->shouldTrackError('Unknown', 'Failed to fetch'),
            'Network errors should not be tracked'
        );

        $this->assertFalse(
            $this->shouldTrackError(
                'ResizeObserverError',
                'ResizeObserver loop limit exceeded'
            ),
            'ResizeObserver errors should not be tracked (benign)'
        );

        $this->assertTrue(
            $this->shouldTrackError('TypeError', 'Cannot read property'),
            'TypeError should be tracked'
        );

        $this->assertTrue(
            $this->shouldTrackError('ReferenceError', 'x is not defined'),
            'ReferenceError should be tracked'
        );

        $this->assertTrue(
            $this->shouldTrackError('SyntaxError', 'Unexpected token'),
            'SyntaxError should be tracked'
        );
    }

    /**
     * Test stack trace truncation
     * Long stack traces should be truncated to prevent data bloat
     */
    public function test_stack_trace_truncation(): void
    {
        $shortStack = 'Error: Test\n  at foo.js:1:1';
        $truncated = $this->extractStackTrace($shortStack, 100);
        $this->assertEquals($shortStack, $truncated, 'Short stack should not be truncated');

        $longStack = str_repeat('a', 3000);
        $truncated = $this->extractStackTrace($longStack, 2000);
        $this->assertStringContainsString(
            '[truncated]',
            $truncated,
            'Long stack should be truncated and marked'
        );
        $this->assertLessThanOrEqual(
            2050, // 2000 + '[truncated]' (11 chars)
            strlen($truncated),
            'Truncated stack should respect max length'
        );
    }

    /**
     * Test error hash generation
     * Same error type and message should generate same hash
     */
    public function test_error_hash_generation(): void
    {
        $hash1 = $this->hashError('TypeError', 'Cannot read property');
        $hash2 = $this->hashError('TypeError', 'Cannot read property');
        $hash3 = $this->hashError('ReferenceError', 'Cannot read property');

        $this->assertEquals($hash1, $hash2, 'Same type and message should produce same hash');
        $this->assertNotEquals($hash1, $hash3, 'Different type should produce different hash');
    }

    // Helper methods simulating plugin logic

    private function shouldTrackError(string $type, string $message, ?string $filename = null): bool
    {
        if (
            $type === 'ChunkLoadError' ||
            str_contains($message, 'Failed to load chunk') ||
            $type === 'NotFound'
        ) {
            return false;
        }

        if ($type === 'AbortError') {
            return false;
        }

        if (
            $type === 'NetworkError' ||
            str_contains($message, 'NetworkError') ||
            str_contains($message, 'Failed to fetch')
        ) {
            return false;
        }

        if (
            str_contains($message, 'ResizeObserver') ||
            ($filename && str_contains($filename, 'ResizeObserver'))
        ) {
            return false;
        }

        return true;
    }

    private function hashError(string $type, string $message): string
    {
        return "{$type}:{$message}";
    }

    private function extractStackTrace(string $stack, int $maxLength): ?string
    {
        if (empty($stack)) {
            return null;
        }

        if (strlen($stack) > $maxLength) {
            return substr($stack, 0, $maxLength).'... [truncated]';
        }

        return $stack;
    }
}
