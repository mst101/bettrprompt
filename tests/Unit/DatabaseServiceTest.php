<?php

use App\Services\DatabaseService;
use Illuminate\Database\QueryException;

test('retry on deadlock succeeds on first attempt', function () {
    $result = DatabaseService::retryOnDeadlock(function () {
        return 'success';
    });

    expect($result)->toBe('success');
});

test('retry on deadlock retries on deadlock error', function () {
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

    expect($attempts)->toBe(2)
        ->and($result)->toBe('success after retry');
});

test('retry on deadlock throws non deadlock errors immediately', function () {
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
})->throws(QueryException::class);

test('transaction executes successfully', function () {
    $result = DatabaseService::transaction(function () {
        return 'transaction success';
    });

    expect($result)->toBe('transaction success');
});

test('is constraint violation detects duplicate errors', function () {
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

    expect(DatabaseService::isConstraintViolation($exception))->toBeTrue();
});

test('is constraint violation returns false for other exceptions', function () {
    $exception = new \Exception('Not a database error');

    expect(DatabaseService::isConstraintViolation($exception))->toBeFalse();
});

test('is deadlock detects deadlock errors', function () {
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

    expect(DatabaseService::isDeadlock($exception))->toBeTrue();
});

test('is deadlock returns false for other exceptions', function () {
    $exception = new \Exception('Not a deadlock');

    expect(DatabaseService::isDeadlock($exception))->toBeFalse();
});
