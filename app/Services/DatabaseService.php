<?php

namespace App\Services;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class DatabaseService
{
    /**
     * Execute a database operation with retry logic for deadlocks
     *
     * @param  callable  $callback  The database operation to execute
     * @param  int  $maxAttempts  Maximum number of retry attempts
     * @return mixed The result of the callback
     *
     * @throws QueryException
     */
    public static function retryOnDeadlock(callable $callback, int $maxAttempts = 3): mixed
    {
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            try {
                return $callback();
            } catch (QueryException $e) {
                $attempts++;

                // Check if it's a deadlock error
                // MySQL: 1213 (Deadlock found)
                // Postgres: 40P01 (deadlock_detected)
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

        // Should never reach here
        throw new RuntimeException('Failed to execute callback after retries');
    }

    /**
     * Execute a database transaction with proper error handling
     *
     * @param  callable  $callback  The transaction operations
     * @param  int  $attempts  Maximum number of transaction attempts
     * @return mixed The result of the callback
     *
     * @throws QueryException|Throwable
     */
    public static function transaction(callable $callback, int $attempts = 1): mixed
    {
        try {
            return DB::transaction($callback, $attempts);
        } catch (QueryException $e) {
            Log::error('Database transaction failed', [
                'error' => $e->getMessage(),
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
                'error_code' => $e->errorInfo[1] ?? null,
            ]);
            throw $e;
        } catch (Throwable $e) {
            Log::error('Unexpected error in database transaction', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Check if an exception is a constraint violation
     */
    public static function isConstraintViolation(Throwable $exception): bool
    {
        if (! $exception instanceof QueryException) {
            return false;
        }

        $errorCode = $exception->errorInfo[1] ?? null;

        // MySQL: 1062 (duplicate), 1451/1452 (foreign key)
        // Postgres: 23505 (unique), 23503 (foreign key)
        return in_array($errorCode, [1062, 1451, 1452, '23505', '23503']);
    }

    /**
     * Check if an exception is a deadlock
     */
    public static function isDeadlock(Throwable $exception): bool
    {
        if (! $exception instanceof QueryException) {
            return false;
        }

        $errorCode = $exception->errorInfo[1] ?? null;

        // MySQL: 1213, Postgres: 40P01
        return in_array($errorCode, [1213, '40P01']);
    }
}
