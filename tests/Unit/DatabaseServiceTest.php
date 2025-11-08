<?php

namespace Tests\Unit;

use App\Services\DatabaseService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_retry_on_deadlock_succeeds_on_first_attempt(): void
    {
        $result = DatabaseService::retryOnDeadlock(function () {
            return 'success';
        });

        $this->assertEquals('success', $result);
    }

    public function test_retry_on_deadlock_retries_on_deadlock_error(): void
    {
        $attempts = 0;

        // Mock a deadlock on first attempt, success on second
        $result = DatabaseService::retryOnDeadlock(function () use (&$attempts) {
            $attempts++;

            if ($attempts === 1) {
                // Simulate PostgreSQL deadlock error (error code 40P01)
                $pdoException = new \PDOException('deadlock detected');
                $exception = new QueryException(
                    'pgsql',
                    'UPDATE users SET name = ?',
                    ['test'],
                    $pdoException
                );

                // Set the PostgreSQL deadlock error code using reflection
                $reflection = new \ReflectionClass($exception);
                $property = $reflection->getProperty('errorInfo');
                $property->setAccessible(true);
                $property->setValue($exception, [null, '40P01', 'deadlock detected']);

                throw $exception;
            }

            return 'success after retry';
        });

        $this->assertEquals(2, $attempts);
        $this->assertEquals('success after retry', $result);
    }

    public function test_retry_on_deadlock_throws_non_deadlock_errors_immediately(): void
    {
        $this->expectException(QueryException::class);

        DatabaseService::retryOnDeadlock(function () {
            // Simulate a different database error (not a deadlock) - PostgreSQL unique violation
            $pdoException = new \PDOException('unique constraint violation');
            $exception = new QueryException(
                'pgsql',
                'INSERT INTO users VALUES (?)',
                ['test'],
                $pdoException
            );

            // Set a non-deadlock error code (23505 = unique violation in PostgreSQL)
            $reflection = new \ReflectionClass($exception);
            $property = $reflection->getProperty('errorInfo');
            $property->setAccessible(true);
            $property->setValue($exception, [null, '23505', 'unique constraint violation']);

            throw $exception;
        });
    }

    public function test_transaction_executes_successfully(): void
    {
        $result = DatabaseService::transaction(function () {
            return 'transaction success';
        });

        $this->assertEquals('transaction success', $result);
    }

    public function test_is_constraint_violation_detects_duplicate_errors(): void
    {
        $pdoException = new \PDOException('unique constraint violation');

        // Create a QueryException with duplicate error code (23505 for PostgreSQL)
        $exception = new QueryException(
            'pgsql',
            'INSERT INTO users VALUES (?)',
            ['test'],
            $pdoException
        );

        // Set the error code using reflection since errorInfo is protected
        $reflection = new \ReflectionClass($exception);
        $property = $reflection->getProperty('errorInfo');
        $property->setAccessible(true);
        $property->setValue($exception, [null, '23505', 'unique constraint violation']);

        $this->assertTrue(DatabaseService::isConstraintViolation($exception));
    }

    public function test_is_constraint_violation_returns_false_for_other_exceptions(): void
    {
        $exception = new \Exception('Not a database error');

        $this->assertFalse(DatabaseService::isConstraintViolation($exception));
    }

    public function test_is_deadlock_detects_deadlock_errors(): void
    {
        $pdoException = new \PDOException('deadlock detected');

        $exception = new QueryException(
            'pgsql',
            'UPDATE users SET name = ?',
            ['test'],
            $pdoException
        );

        // Set the deadlock error code (40P01 for PostgreSQL)
        $reflection = new \ReflectionClass($exception);
        $property = $reflection->getProperty('errorInfo');
        $property->setAccessible(true);
        $property->setValue($exception, [null, '40P01', 'deadlock detected']);

        $this->assertTrue(DatabaseService::isDeadlock($exception));
    }

    public function test_is_deadlock_returns_false_for_other_exceptions(): void
    {
        $exception = new \Exception('Not a deadlock');

        $this->assertFalse(DatabaseService::isDeadlock($exception));
    }
}
