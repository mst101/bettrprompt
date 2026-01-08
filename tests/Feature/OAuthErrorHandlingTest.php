<?php

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

/**
 * Helper to create a mocked Socialite user
 */
function createMockOAuthUser(string $id, ?string $email, ?string $name = null, ?string $avatar = null)
{
    $mockUser = Mockery::mock('Laravel\Socialite\Two\User');
    $mockUser->shouldReceive('getAttribute')->with('id')->andReturn($id);
    $mockUser->shouldReceive('getAttribute')->with('email')->andReturn($email);
    if ($name) {
        $mockUser->shouldReceive('getAttribute')->with('name')->andReturn($name);
    }
    if ($avatar) {
        $mockUser->shouldReceive('getAttribute')->with('avatar')->andReturn($avatar);
    }
    $mockUser->id = $id;
    $mockUser->email = $email;
    $mockUser->name = $name;
    $mockUser->avatar = $avatar;

    return $mockUser;
}

test('handles invalid state exception', function () {
    Socialite::shouldReceive('driver')->with('google')->andReturnSelf();
    Socialite::shouldReceive('user')->andThrow(new InvalidStateException);

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirect(route('login', ['locale' => $this->testLocale]));
    $response->assertSessionHas('error', 'Authentication session expired. Please try logging in again.');
});

test('handles network errors from oauth provider', function () {
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

    $response->assertRedirect(route('login', ['locale' => $this->testLocale]));
    $response->assertSessionHas('error', 'Failed to communicate with Google. Please try again later.');
});

test('handles missing or invalid email from oauth provider', function (string $googleId, ?string $email, string $expectedError) {
    $mockUser = createMockOAuthUser($googleId, $email, 'Test User', 'https://example.com/avatar.jpg');

    Socialite::shouldReceive('driver')->with('google')->andReturnSelf();
    Socialite::shouldReceive('user')->andReturn($mockUser);

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirect(route('login', ['locale' => $this->testLocale]));
    $response->assertSessionHas('error', $expectedError);
})->with([
    ['google123', null, 'Could not retrieve your account information from Google. Please try again.'],
    ['google123', 'not-an-email', 'Invalid email address received from Google. Please try again.'],
]);

test('creates new user from oauth data', function () {
    $mockUser = createMockOAuthUser('google123', 'newuser@example.com', 'New User', 'https://example.com/avatar.jpg');

    Socialite::shouldReceive('driver')->with('google')->andReturnSelf();
    Socialite::shouldReceive('user')->andReturn($mockUser);

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirect(route('prompt-builder.index'));

    $this->assertDatabaseHas('users', [
        'email' => 'newuser@example.com',
        'google_id' => 'google123',
        'name' => 'New User',
    ]);

    $this->assertAuthenticated();
});

test('updates existing user with google id', function () {
    $existingUser = User::factory()->create([
        'email' => 'existing@example.com',
        'google_id' => null,
    ]);

    $mockUser = createMockOAuthUser('google456', 'existing@example.com', 'Existing User', 'https://example.com/new-avatar.jpg');

    Socialite::shouldReceive('driver')->with('google')->andReturnSelf();
    Socialite::shouldReceive('user')->andReturn($mockUser);

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirect(route('prompt-builder.index'));

    $existingUser->refresh();
    expect($existingUser->google_id)->toBe('google456')
        ->and($existingUser->avatar)->toBe('https://example.com/new-avatar.jpg');

    $this->assertAuthenticated();
    expect(auth()->id())->toBe($existingUser->id);
});

test('finds existing user by google id', function () {
    $existingUser = User::factory()->create([
        'email' => 'user@example.com',
        'google_id' => 'google789',
    ]);

    $mockUser = createMockOAuthUser('google789', 'user@example.com', 'User Name', 'https://example.com/avatar.jpg');

    Socialite::shouldReceive('driver')->with('google')->andReturnSelf();
    Socialite::shouldReceive('user')->andReturn($mockUser);

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirect(route('prompt-builder.index'));

    $this->assertAuthenticated();
    expect(auth()->id())->toBe($existingUser->id);

    expect(User::all())->toHaveCount(1);
});

test('redirect to google handles errors', function () {
    // Mock Socialite to throw an exception
    Socialite::shouldReceive('driver')->with('google')->andThrow(new \Exception('Configuration error'));

    $response = $this->get(route('auth.google'));

    $response->assertRedirect(route('home'));
    $response->assertSessionHas('error', 'Unable to connect to Google. Please try again later.');
});
