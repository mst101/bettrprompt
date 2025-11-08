<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DatabaseService
{
    /**
     * Execute a database operation with retry logic for deadlocks
     *
     * @param  callable  $callback  The database operation to execute
     * @param  int  $maxAttempts  Maximum number of retry attempts
     * @return mixed The result of the callback
     *
     * @throws \Illuminate\Database\QueryException
     */
    public static function retryOnDeadlock(callable $callback, int $maxAttempts = 3)
    {
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            try {
                return $callback();
            } catch (\Illuminate\Database\QueryException $e) {
                $attempts++;

                // Check if it's a deadlock error
                // MySQL: 1213 (Deadlock found)
                // PostgreSQL: 40P01 (deadlock_detected)
                $errorCode = $e->errorInfo[1] ?? null;
                $isDeadlock = in_array($errorCode, [1213, '40P01']);

                if ($isDeadlock && $attempts < $maxAttempts) {
                    Log::warning('Database deadlock detected, retrying', [
                        'attempt' => $attempts,
                        'max_attempts' => $maxAttempts,
                        'error' => $e->getMessage(),
                    ]);

                    // Exponential backoff: 100ms, 200ms, 400ms
                    usleep(pow(2, $attempts) * 100000);
                    continue;
                }

                // Not a deadlock or max attempts reached
                throw $e;
            }
        }
    }

    /**
     * Execute a database transaction with proper error handling
     *
     * @param  callable  $callback  The transaction operations
     * @param  int  $attempts  Maximum number of transaction attempts
     * @return mixed The result of the callback
     *
     * @throws \Illuminate\Database\QueryException
     */
    public static function transaction(callable $callback, int $attempts = 1)
    {
        try {
            return DB::transaction($callback, $attempts);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database transaction failed', [
                'error' => $e->getMessage(),
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
                'error_code' => $e->errorInfo[1] ?? null,
            ]);
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Unexpected error in database transaction', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Execute a database operation with comprehensive error handling
     *
     * @param  callable  $callback  The database operation
     * @param  string  $operation  Description of the operation (for logging)
     * @param  array  $context  Additional context for logging
     * @return mixed The result of the callback
     *
     * @throws \Illuminate\Database\QueryException
     */
    public static function safeExecute(callable $callback, string $operation = 'database operation', array $context = [])
    {
        try {
            return $callback();
        } catch (\Illuminate\Database\QueryException $e) {
            $errorCode = $e->errorInfo[1] ?? null;

            // Log with context
            Log::error("Database error during {$operation}", array_merge([
                'error' => $e->getMessage(),
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
                'error_code' => $errorCode,
            ], $context));

            // Provide user-friendly error messages based on error type
            $userMessage = self::getUserFriendlyMessage($errorCode, $e);

            // Throw a custom exception with user-friendly message
            throw new \RuntimeException($userMessage, 0, $e);
        }
    }

    /**
     * Get a user-friendly error message based on the database error
     *
     * @param  mixed  $errorCode  The database error code
     * @param  \Illuminate\Database\QueryException  $exception  The original exception
     * @return string User-friendly error message
     */
    private static function getUserFriendlyMessage($errorCode, \Illuminate\Database\QueryException $exception): string
    {
        // Check for common error codes
        $message = match ($errorCode) {
            // MySQL: Duplicate entry
            1062 => 'This record already exists. Please use different values.',

            // PostgreSQL: Unique violation
            '23505' => 'This record already exists. Please use different values.',

            // MySQL: Foreign key constraint fails
            1451, 1452 => 'This operation cannot be completed due to related records.',

            // PostgreSQL: Foreign key violation
            '23503' => 'This operation cannot be completed due to related records.',

            // MySQL: Data too long
            1406 => 'One or more values are too long. Please use shorter values.',

            // MySQL: Deadlock
            1213 => 'A temporary database conflict occurred. Please try again.',

            // PostgreSQL: Deadlock
            '40P01' => 'A temporary database conflict occurred. Please try again.',

            // Connection errors
            2002, 2006, '08006' => 'Database connection lost. Please try again.',

            default => 'A database error occurred. Please try again.',
        };

        // In debug mode, append the original message
        if (config('app.debug')) {
            $message .= ' (Debug: '.$exception->getMessage().')';
        }

        return $message;
    }

    /**
     * Check if an exception is a constraint violation
     *
     * @param  \Throwable  $exception
     * @return bool
     */
    public static function isConstraintViolation(\Throwable $exception): bool
    {
        if (! $exception instanceof \Illuminate\Database\QueryException) {
            return false;
        }

        $errorCode = $exception->errorInfo[1] ?? null;

        // MySQL: 1062 (duplicate), 1451/1452 (foreign key)
        // PostgreSQL: 23505 (unique), 23503 (foreign key)
        return in_array($errorCode, [1062, 1451, 1452, '23505', '23503']);
    }

    /**
     * Check if an exception is a deadlock
     *
     * @param  \Throwable  $exception
     * @return bool
     */
    public static function isDeadlock(\Throwable $exception): bool
    {
        if (! $exception instanceof \Illuminate\Database\QueryException) {
            return false;
        }

        $errorCode = $exception->errorInfo[1] ?? null;

        // MySQL: 1213, PostgreSQL: 40P01
        return in_array($errorCode, [1213, '40P01']);
    }
}
