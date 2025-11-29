<?php

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

test('handles invalid state exception', function () {
    // Mock Socialite to throw InvalidStateException
    Socialite::shouldReceive('driver')->with('google')->andReturnSelf();
    Socialite::shouldReceive('user')->andThrow(new InvalidStateException);

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error', 'Authentication session expired. Please try logging in again.');
});

test('handles missing email from oauth provider', function () {
    // Mock OAuth user with missing email
    $mockUser = Mockery::mock('Laravel\Socialite\Two\User');
    $mockUser->shouldReceive('getAttribute')->with('id')->andReturn('google123');
    $mockUser->shouldReceive('getAttribute')->with('email')->andReturn(null);
    $mockUser->id = 'google123';
    $mockUser->email = null;

    Socialite::shouldReceive('driver')->with('google')->andReturnSelf();
    Socialite::shouldReceive('user')->andReturn($mockUser);

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error', 'Could not retrieve your account information from Google. Please try again.');
});

test('handles invalid email from oauth provider', function () {
    // Mock OAuth user with invalid email format
    $mockUser = Mockery::mock('Laravel\Socialite\Two\User');
    $mockUser->shouldReceive('getAttribute')->with('id')->andReturn('google123');
    $mockUser->shouldReceive('getAttribute')->with('email')->andReturn('not-an-email');
    $mockUser->shouldReceive('getAttribute')->with('name')->andReturn('Test User');
    $mockUser->shouldReceive('getAttribute')->with('avatar')->andReturn('https://example.com/avatar.jpg');
    $mockUser->id = 'google123';
    $mockUser->email = 'not-an-email';
    $mockUser->name = 'Test User';
    $mockUser->avatar = 'https://example.com/avatar.jpg';

    Socialite::shouldReceive('driver')->with('google')->andReturnSelf();
    Socialite::shouldReceive('user')->andReturn($mockUser);

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error', 'Invalid email address received from Google. Please try again.');
});

test('creates new user from oauth data', function () {
    // Mock OAuth user with valid data
    $mockUser = Mockery::mock('Laravel\Socialite\Two\User');
    $mockUser->shouldReceive('getAttribute')->with('id')->andReturn('google123');
    $mockUser->shouldReceive('getAttribute')->with('email')->andReturn('newuser@example.com');
    $mockUser->shouldReceive('getAttribute')->with('name')->andReturn('New User');
    $mockUser->shouldReceive('getAttribute')->with('avatar')->andReturn('https://example.com/avatar.jpg');
    $mockUser->id = 'google123';
    $mockUser->email = 'newuser@example.com';
    $mockUser->name = 'New User';
    $mockUser->avatar = 'https://example.com/avatar.jpg';

    Socialite::shouldReceive('driver')->with('google')->andReturnSelf();
    Socialite::shouldReceive('user')->andReturn($mockUser);

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirect(route('prompt-builder.index'));

    // Verify user was created
    $this->assertDatabaseHas('users', [
        'email' => 'newuser@example.com',
        'google_id' => 'google123',
        'name' => 'New User',
    ]);

    // Verify user is authenticated
    $this->assertAuthenticated();
});

test('updates existing user with google id', function () {
    // Create existing user without Google ID
    $existingUser = User::factory()->create([
        'email' => 'existing@example.com',
        'google_id' => null,
    ]);

    // Mock OAuth user with same email
    $mockUser = Mockery::mock('Laravel\Socialite\Two\User');
    $mockUser->shouldReceive('getAttribute')->with('id')->andReturn('google456');
    $mockUser->shouldReceive('getAttribute')->with('email')->andReturn('existing@example.com');
    $mockUser->shouldReceive('getAttribute')->with('name')->andReturn('Existing User');
    $mockUser->shouldReceive('getAttribute')->with('avatar')->andReturn('https://example.com/new-avatar.jpg');
    $mockUser->id = 'google456';
    $mockUser->email = 'existing@example.com';
    $mockUser->name = 'Existing User';
    $mockUser->avatar = 'https://example.com/new-avatar.jpg';

    Socialite::shouldReceive('driver')->with('google')->andReturnSelf();
    Socialite::shouldReceive('user')->andReturn($mockUser);

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirect(route('prompt-builder.index'));

    // Verify user was updated with Google ID
    $existingUser->refresh();
    expect($existingUser->google_id)->toBe('google456')
        ->and($existingUser->avatar)->toBe('https://example.com/new-avatar.jpg');

    // Verify user is authenticated
    $this->assertAuthenticated();
    expect(auth()->id())->toBe($existingUser->id);
});

test('finds existing user by google id', function () {
    // Create existing user with Google ID
    $existingUser = User::factory()->create([
        'email' => 'user@example.com',
        'google_id' => 'google789',
    ]);

    // Mock OAuth user with same Google ID
    $mockUser = Mockery::mock('Laravel\Socialite\Two\User');
    $mockUser->shouldReceive('getAttribute')->with('id')->andReturn('google789');
    $mockUser->shouldReceive('getAttribute')->with('email')->andReturn('user@example.com');
    $mockUser->shouldReceive('getAttribute')->with('name')->andReturn('User Name');
    $mockUser->shouldReceive('getAttribute')->with('avatar')->andReturn('https://example.com/avatar.jpg');
    $mockUser->id = 'google789';
    $mockUser->email = 'user@example.com';
    $mockUser->name = 'User Name';
    $mockUser->avatar = 'https://example.com/avatar.jpg';

    Socialite::shouldReceive('driver')->with('google')->andReturnSelf();
    Socialite::shouldReceive('user')->andReturn($mockUser);

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirect(route('prompt-builder.index'));

    // Verify user is authenticated as the existing user
    $this->assertAuthenticated();
    expect(auth()->id())->toBe($existingUser->id);

    // Verify no new user was created
    expect(User::all())->toHaveCount(1);
});

test('handles network errors from oauth provider', function () {
    // Mock Socialite to throw a Guzzle ClientException
    $mockResponse = Mockery::mock('Psr\Http\Message\ResponseInterface');
    $mockResponse->shouldReceive('getStatusCode')->andReturn(500);

    Socialite::shouldReceive('driver')->with('google')->andReturnSelf();
    Socialite::shouldReceive('user')->andThrow(
        new \GuzzleHttp\Exception\ClientException(
            'Request failed',
            Mockery::mock('Psr\Http\Message\RequestInterface'),
            $mockResponse
        )
    );

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error', 'Failed to communicate with Google. Please try again later.');
});

test('redirect to google handles errors', function () {
    // Mock Socialite to throw an exception
    Socialite::shouldReceive('driver')->with('google')->andThrow(new \Exception('Configuration error'));

    $response = $this->get(route('auth.google'));

    $response->assertRedirect(route('home'));
    $response->assertSessionHas('error', 'Unable to connect to Google. Please try again later.');
});
