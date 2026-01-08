<?php

use App\Models\User;

test('profile page requires authentication', function () {
    $response = $this->getLocale('/profile');

    $response->assertRedirect(route('login'));
});

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->getLocale('/profile');

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patchLocale('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect($this->localeRoute('profile.edit'));

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patchLocale('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect($this->localeRoute('profile.edit'));

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->deleteLocale('/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertNull($user->fresh());
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from($this->localeRoute('profile.edit'))
        ->deleteLocale('/profile', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrors('password')
        ->assertRedirect($this->localeRoute('profile.edit'));

    $this->assertNotNull($user->fresh());
});

// Location update tests
test('user location can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patchLocale('/profile/location', [
            'countryCode' => 'GB',
            'region' => 'England',
            'city' => 'London',
            'timezone' => 'Europe/London',
            'currencyCode' => 'GBP',
            'languageCode' => 'en-GB',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect($this->localeRoute('profile.edit'));

    $user->refresh();

    $this->assertSame('GB', $user->country_code);
    $this->assertSame('England', $user->region);
    $this->assertSame('London', $user->city);
    $this->assertSame('Europe/London', $user->timezone);
    $this->assertSame('GBP', $user->currency_code);
    $this->assertSame('en-GB', $user->language_code);
    $this->assertTrue($user->location_manually_set);
});

test('user location can be partially updated', function () {
    $user = User::factory()->create([
        'country_code' => 'US',
        'city' => 'New York',
        'timezone' => 'America/New_York',
    ]);

    $response = $this
        ->actingAs($user)
        ->patchLocale('/profile/location', [
            'city' => 'San Francisco',
            'timezone' => 'America/Los_Angeles',
        ]);

    $response->assertSessionHasNoErrors();

    $user->refresh();

    $this->assertSame('US', $user->country_code); // Unchanged
    $this->assertSame('San Francisco', $user->city); // Updated
    $this->assertSame('America/Los_Angeles', $user->timezone); // Updated
});

test('location country code must be 2 characters', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from($this->localeRoute('profile.edit'))
        ->patchLocale('/profile/location', [
            'country_code' => 'USA', // Invalid: 3 characters
        ]);

    $response->assertSessionHasErrors('country_code');
});

test('location currency code must be 3 characters', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from($this->localeRoute('profile.edit'))
        ->patchLocale('/profile/location', [
            'currency_code' => 'GB', // Invalid: 2 characters
        ]);

    $response->assertSessionHasErrors('currency_code');
});

test('user location can be cleared', function () {
    $user = User::factory()->create([
        'country_code' => 'GB',
        'region' => 'England',
        'city' => 'London',
        'timezone' => 'Europe/London',
        'currency_code' => 'GBP',
        'language_code' => 'en-GB',
        'location_manually_set' => true,
    ]);

    $response = $this
        ->actingAs($user)
        ->deleteLocale('/profile/location');

    $response->assertSessionHasNoErrors()->assertRedirect($this->localeRoute('profile.edit'));

    $user->refresh();

    $this->assertNull($user->country_code);
    $this->assertNull($user->region);
    $this->assertNull($user->city);
    $this->assertNull($user->timezone);
    $this->assertNull($user->currency_code);
    $this->assertNull($user->language_code);
    $this->assertFalse($user->location_manually_set);
});

// Professional context tests
test('user professional context can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patchLocale('/profile/professional', [
            'jobTitle' => 'Senior Developer',
            'industry' => 'Technology',
            'experienceLevel' => 'senior',
            'companySize' => 'enterprise',
        ]);

    $response->assertSessionHasNoErrors()->assertRedirect($this->localeRoute('profile.edit'));

    $user->refresh();

    $this->assertSame('Senior Developer', $user->job_title);
    $this->assertSame('Technology', $user->industry);
    $this->assertSame('senior', $user->experience_level);
    $this->assertSame('enterprise', $user->company_size);
});

test('user professional context can be cleared', function () {
    $user = User::factory()->create([
        'job_title' => 'Developer',
        'industry' => 'Tech',
        'experience_level' => 'mid',
        'company_size' => 'medium',
    ]);

    $response = $this
        ->actingAs($user)
        ->deleteLocale('/profile/professional');

    $response->assertSessionHasNoErrors()->assertRedirect($this->localeRoute('profile.edit'));

    $user->refresh();

    $this->assertNull($user->job_title);
    $this->assertNull($user->industry);
    $this->assertNull($user->experience_level);
    $this->assertNull($user->company_size);
});

// Team context tests
test('user team context can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patchLocale('/profile/team', [
            'teamSize' => 'medium',
            'teamRole' => 'lead',
            'workMode' => 'hybrid',
        ]);

    $response->assertSessionHasNoErrors()->assertRedirect($this->localeRoute('profile.edit'));

    $user->refresh();

    $this->assertSame('medium', $user->team_size);
    $this->assertSame('lead', $user->team_role);
    $this->assertSame('hybrid', $user->work_mode);
});

test('user team context can be cleared', function () {
    $user = User::factory()->create([
        'team_size' => 'small',
        'team_role' => 'individual',
        'work_mode' => 'remote',
    ]);

    $response = $this
        ->actingAs($user)
        ->deleteLocale('/profile/team');

    $response->assertSessionHasNoErrors()->assertRedirect($this->localeRoute('profile.edit'));

    $user->refresh();

    $this->assertNull($user->team_size);
    $this->assertNull($user->team_role);
    $this->assertNull($user->work_mode);
});

// Budget preferences tests
test('user budget preferences can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patchLocale('/profile/budget', [
            'budgetConsciousness' => 'free_first',
        ]);

    $response->assertSessionHasNoErrors()->assertRedirect($this->localeRoute('profile.edit'));

    $user->refresh();

    $this->assertSame('free_first', $user->budget_consciousness);
});

test('user budget preferences can be cleared', function () {
    $user = User::factory()->create([
        'budget_consciousness' => 'premium_ok',
    ]);

    $response = $this
        ->actingAs($user)
        ->deleteLocale('/profile/budget');

    $response->assertSessionHasNoErrors()->assertRedirect($this->localeRoute('profile.edit'));

    $user->refresh();

    $this->assertNull($user->budget_consciousness);
});

// Tools preferences tests
test('user tools preferences can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patchLocale('/profile/tools', [
            'preferredTools' => ['vscode', 'docker', 'git'],
            'primaryProgrammingLanguage' => 'typescript',
        ]);

    $response->assertSessionHasNoErrors()->assertRedirect($this->localeRoute('profile.edit'));

    $user->refresh();

    $this->assertSame(['vscode', 'docker', 'git'], $user->preferred_tools);
    $this->assertSame('typescript', $user->primary_programming_language);
});

test('user tools preferences can be cleared', function () {
    $user = User::factory()->create([
        'preferred_tools' => ['vscode', 'docker'],
        'primary_programming_language' => 'python',
    ]);

    $response = $this
        ->actingAs($user)
        ->deleteLocale('/profile/tools');

    $response->assertSessionHasNoErrors()->assertRedirect($this->localeRoute('profile.edit'));

    $user->refresh();

    $this->assertNull($user->preferred_tools);
    $this->assertNull($user->primary_programming_language);
});

test('unauthenticated user cannot update location', function () {
    $response = $this
        ->from($this->localeRoute('profile.edit'))
        ->patchLocale('/profile/location', [
            'countryCode' => 'GB',
        ]);

    $response->assertRedirect(route('login'));
});

test('unauthenticated user cannot update professional context', function () {
    $response = $this
        ->from($this->localeRoute('profile.edit'))
        ->patchLocale('/profile/professional', [
            'jobTitle' => 'Developer',
        ]);

    $response->assertRedirect(route('login'));
});

test('unauthenticated user cannot update team context', function () {
    $response = $this
        ->from($this->localeRoute('profile.edit'))
        ->patchLocale('/profile/team', [
            'teamSize' => '10-20',
        ]);

    $response->assertRedirect(route('login'));
});

test('unauthenticated user cannot update budget preferences', function () {
    $response = $this
        ->from($this->localeRoute('profile.edit'))
        ->patchLocale('/profile/budget', [
            'budgetConsciousness' => 'cost-focused',
        ]);

    $response->assertRedirect(route('login'));
});

test('unauthenticated user cannot update tools preferences', function () {
    $response = $this
        ->from($this->localeRoute('profile.edit'))
        ->patchLocale('/profile/tools', [
            'primaryProgrammingLanguage' => 'typescript',
        ]);

    $response->assertRedirect(route('login'));
});
