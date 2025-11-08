<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Mockery;
use Tests\TestCase;

class OAuthErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    public function test_handles_invalid_state_exception(): void
    {
        // Mock Socialite to throw InvalidStateException
        Socialite::shouldReceive('driver')->with('google')->andReturnSelf();
        Socialite::shouldReceive('user')->andThrow(new InvalidStateException());

        $response = $this->get(route('auth.google.callback'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'Authentication session expired. Please try logging in again.');
    }

    public function test_handles_missing_email_from_oauth_provider(): void
    {
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
    }

    public function test_handles_invalid_email_from_oauth_provider(): void
    {
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
    }

    public function test_creates_new_user_from_oauth_data(): void
    {
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

        $response->assertRedirect(route('prompt-optimizer.index'));

        // Verify user was created
        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'google_id' => 'google123',
            'name' => 'New User',
        ]);

        // Verify user is authenticated
        $this->assertAuthenticated();
    }

    public function test_updates_existing_user_with_google_id(): void
    {
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

        $response->assertRedirect(route('prompt-optimizer.index'));

        // Verify user was updated with Google ID
        $existingUser->refresh();
        $this->assertEquals('google456', $existingUser->google_id);
        $this->assertEquals('https://example.com/new-avatar.jpg', $existingUser->avatar);

        // Verify user is authenticated
        $this->assertAuthenticated();
        $this->assertEquals($existingUser->id, auth()->id());
    }

    public function test_finds_existing_user_by_google_id(): void
    {
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

        $response->assertRedirect(route('prompt-optimizer.index'));

        // Verify user is authenticated as the existing user
        $this->assertAuthenticated();
        $this->assertEquals($existingUser->id, auth()->id());

        // Verify no new user was created
        $this->assertCount(1, User::all());
    }

    public function test_handles_network_errors_from_oauth_provider(): void
    {
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
    }

    public function test_redirect_to_google_handles_errors(): void
    {
        // Mock Socialite to throw an exception
        Socialite::shouldReceive('driver')->with('google')->andThrow(new \Exception('Configuration error'));

        $response = $this->get(route('auth.google'));

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('error', 'Unable to connect to Google. Please try again later.');
    }
}
