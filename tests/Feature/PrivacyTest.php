<?php

use App\Models\User;
use App\Services\EncryptionService;
use App\Services\RecoveryPhraseService;

test('privacy settings page requires authentication', function () {
    $response = $this->getCountry('/settings/privacy');

    $response->assertRedirect(route('login'));
});

test('privacy settings page is displayed for authenticated users', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->getCountry('/settings/privacy');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Settings/Privacy')
        ->has('privacy')
        ->has('subscription')
    );
});

test('free user cannot enable privacy', function () {
    $user = User::factory()->create([
        'subscription_tier' => 'free',
    ]);

    expect($user->canEnablePrivacy())->toBeFalse();
});

test('pro user can enable privacy', function () {
    $user = User::factory()->create();
    $user->update(['stripe_id' => 'cus_test']);
    $user->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_test',
        'stripe_status' => 'active',
        'stripe_price' => 'price_test',
    ]);

    expect($user->canEnablePrivacy())->toBeTrue();
});

test('begin setup requires pro subscription', function () {
    $user = User::factory()->create([
        'subscription_tier' => 'free',
    ]);

    $response = $this
        ->actingAs($user)
        ->postCountry('/privacy/begin-setup');

    $response->assertRedirect();
    $response->assertSessionHasErrors('privacy');
});

test('begin setup generates recovery phrase for pro user', function () {
    $user = User::factory()->create();
    $user->update(['stripe_id' => 'cus_test']);
    $user->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_test',
        'stripe_status' => 'active',
        'stripe_price' => 'price_test',
    ]);

    $response = $this
        ->actingAs($user)
        ->postCountry('/privacy/begin-setup');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Settings/PrivacySetup')
        ->has('recoveryPhrase')
        ->where('step', 'show_phrase')
    );

    // Verify recovery phrase is stored in session
    expect(session('privacy_setup.recovery_phrase'))->not()->toBeNull();
    expect(session('privacy_setup.dek'))->not()->toBeNull();
});

test('confirm setup validates password', function () {
    $user = User::factory()->create([
        'password' => bcrypt('correct-password'),
    ]);

    // Simulate setup session
    session([
        'privacy_setup.dek' => encrypt('test-dek'),
        'privacy_setup.recovery_phrase' => 'word1 word2 word3 word4 word5 word6 word7 word8 word9 word10 word11 word12',
    ]);

    $response = $this
        ->actingAs($user)
        ->postCountry('/privacy/confirm-setup', [
            'confirmation_words' => [0 => 'word1', 3 => 'word4', 7 => 'word8'],
            'password' => 'wrong-password',
        ]);

    $response->assertSessionHasErrors('password');
});

test('confirm setup validates recovery phrase words', function () {
    $user = User::factory()->create([
        'password' => bcrypt('correct-password'),
    ]);

    session([
        'privacy_setup.dek' => encrypt('test-dek'),
        'privacy_setup.recovery_phrase' => 'word1 word2 word3 word4 word5 word6 word7 word8 word9 word10 word11 word12',
    ]);

    $response = $this
        ->actingAs($user)
        ->postCountry('/privacy/confirm-setup', [
            'confirmation_words' => [0 => 'wrong', 3 => 'word4', 7 => 'word8'],
            'password' => 'correct-password',
        ]);

    $response->assertSessionHasErrors('confirmation_words');
});

test('confirm setup enables privacy for user', function () {
    $user = User::factory()->create([
        'password' => bcrypt('correct-password'),
    ]);

    $encryptionService = app(EncryptionService::class);
    $dek = $encryptionService->generateDek();

    session([
        'privacy_setup.dek' => encrypt($dek),
        'privacy_setup.recovery_phrase' => 'word1 word2 word3 word4 word5 word6 word7 word8 word9 word10 word11 word12',
    ]);

    $response = $this
        ->actingAs($user)
        ->postCountry('/privacy/confirm-setup', [
            'confirmation_words' => [0 => 'word1', 3 => 'word4', 7 => 'word8'],
            'password' => 'correct-password',
        ]);

    $response->assertRedirect($this->countryRoute('settings.privacy'));

    $user->refresh();
    expect($user->privacy_enabled)->toBeTrue();
    expect($user->encrypted_dek)->not()->toBeNull();
    expect($user->recovery_dek)->not()->toBeNull();
    expect($user->dek_created_at)->not()->toBeNull();
});

test('unlock privacy with correct password', function () {
    $encryptionService = app(EncryptionService::class);
    $dek = $encryptionService->generateDek();

    $user = User::factory()->create([
        'password' => bcrypt('correct-password'),
        'privacy_enabled' => true,
        'encrypted_dek' => $encryptionService->wrapDekWithPassword($dek, 'correct-password'),
        'recovery_dek' => $encryptionService->wrapDekWithRecovery($dek, 'test recovery phrase words'),
        'dek_created_at' => now(),
    ]);

    $response = $this
        ->actingAs($user)
        ->postCountry('/privacy/unlock', [
            'password' => 'correct-password',
        ]);

    $response->assertRedirect();
    expect($encryptionService->hasDekInSession())->toBeTrue();
});

test('unlock privacy with wrong password fails', function () {
    $encryptionService = app(EncryptionService::class);
    $dek = $encryptionService->generateDek();

    $user = User::factory()->create([
        'password' => bcrypt('correct-password'),
        'privacy_enabled' => true,
        'encrypted_dek' => $encryptionService->wrapDekWithPassword($dek, 'correct-password'),
        'dek_created_at' => now(),
    ]);

    $response = $this
        ->actingAs($user)
        ->postCountry('/privacy/unlock', [
            'password' => 'wrong-password',
        ]);

    $response->assertSessionHasErrors('password');
    expect($encryptionService->hasDekInSession())->toBeFalse();
});

test('recovery phrase service generates valid phrases', function () {
    $service = app(RecoveryPhraseService::class);

    $phrase = $service->generate(12);
    $words = explode(' ', $phrase);

    expect(count($words))->toBe(12);
    expect($service->validate($phrase))->toBeTrue();
});

test('recovery phrase validation rejects invalid phrases', function () {
    $service = app(RecoveryPhraseService::class);

    expect($service->validate('not enough words'))->toBeFalse();
    expect($service->validate(''))->toBeFalse();
});

test('encryption service encrypts and decrypts data correctly', function () {
    $service = app(EncryptionService::class);
    $dek = $service->generateDek();
    $plaintext = 'secret message';

    $encrypted = $service->encrypt($plaintext, $dek);
    $decrypted = $service->decrypt($encrypted, $dek);

    expect($encrypted)->not()->toBe($plaintext);
    expect($decrypted)->toBe($plaintext);
});

test('encryption service handles arrays correctly', function () {
    $service = app(EncryptionService::class);
    $dek = $service->generateDek();
    $data = ['key' => 'value', 'nested' => ['a' => 1]];

    $encrypted = $service->encryptArray($data, $dek);
    $decrypted = $service->decryptArray($encrypted, $dek);

    expect($encrypted)->not()->toBe(json_encode($data));
    expect($decrypted)->toBe($data);
});

test('dek can be wrapped and unwrapped with password', function () {
    $service = app(EncryptionService::class);
    $dek = $service->generateDek();
    $password = 'test-password';

    $wrapped = $service->wrapDekWithPassword($dek, $password);
    $unwrapped = $service->unwrapDekWithPassword($wrapped, $password);

    expect($unwrapped)->toBe($dek);
});

test('dek can be wrapped and unwrapped with recovery phrase', function () {
    $service = app(EncryptionService::class);
    $dek = $service->generateDek();
    $phrase = 'word1 word2 word3 word4 word5 word6 word7 word8 word9 word10 word11 word12';

    $wrapped = $service->wrapDekWithRecovery($dek, $phrase);
    $unwrapped = $service->unwrapDekWithRecovery($wrapped, $phrase);

    expect($unwrapped)->toBe($dek);
});

test('disable privacy requires password confirmation', function () {
    $encryptionService = app(EncryptionService::class);
    $dek = $encryptionService->generateDek();

    $user = User::factory()->create([
        'password' => bcrypt('correct-password'),
        'privacy_enabled' => true,
        'encrypted_dek' => $encryptionService->wrapDekWithPassword($dek, 'correct-password'),
        'dek_created_at' => now(),
    ]);

    $response = $this
        ->actingAs($user)
        ->postCountry('/privacy/disable', [
            'password' => 'wrong-password',
            'confirm' => true,
        ]);

    $response->assertSessionHasErrors('password');
    $user->refresh();
    expect($user->privacy_enabled)->toBeTrue();
});

test('disable privacy clears encryption data', function () {
    $encryptionService = app(EncryptionService::class);
    $dek = $encryptionService->generateDek();

    $user = User::factory()->create([
        'password' => bcrypt('correct-password'),
        'privacy_enabled' => true,
        'encrypted_dek' => $encryptionService->wrapDekWithPassword($dek, 'correct-password'),
        'recovery_dek' => $encryptionService->wrapDekWithRecovery($dek, 'test words'),
        'dek_created_at' => now(),
    ]);

    $response = $this
        ->actingAs($user)
        ->postCountry('/privacy/disable', [
            'password' => 'correct-password',
            'confirm' => true,
        ]);

    $response->assertRedirect($this->countryRoute('settings.privacy'));

    $user->refresh();
    expect($user->privacy_enabled)->toBeFalse();
    expect($user->encrypted_dek)->toBeNull();
    expect($user->recovery_dek)->toBeNull();
    expect($user->dek_created_at)->toBeNull();
});

test('privacy status includes all required fields', function () {
    $user = User::factory()->create([
        'privacy_enabled' => false,
    ]);

    $status = $user->getPrivacyStatus();

    expect($status)->toHaveKeys([
        'enabled',
        'canEnable',
        'needsPassword',
        'setupAt',
    ]);
});

test('privacy status is shared with all pages', function () {
    $user = User::factory()->create([
        'privacy_enabled' => false,
    ]);

    $response = $this
        ->actingAs($user)
        ->getCountry('/settings/privacy');

    $response->assertInertia(fn ($page) => $page
        ->has('privacy')
        ->where('privacy.enabled', false)
    );
});

test('recover with valid recovery phrase resets password', function () {
    $encryptionService = app(EncryptionService::class);
    $recoveryPhraseService = app(RecoveryPhraseService::class);
    $dek = $encryptionService->generateDek();
    $recoveryPhrase = $recoveryPhraseService->generate(12);

    $user = User::factory()->create([
        'password' => bcrypt('old-password'),
        'privacy_enabled' => true,
        'encrypted_dek' => $encryptionService->wrapDekWithPassword($dek, 'old-password'),
        'recovery_dek' => $encryptionService->wrapDekWithRecovery($dek, $recoveryPhrase),
        'dek_created_at' => now(),
    ]);

    $response = $this
        ->actingAs($user)
        ->postCountry('/privacy/recover', [
            'recovery_phrase' => $recoveryPhrase,
            'new_password' => 'new-password',
            'new_password_confirmation' => 'new-password',
        ]);

    $response->assertRedirect($this->countryRoute('settings.privacy'));

    $user->refresh();
    expect(\Illuminate\Support\Facades\Hash::check('new-password', $user->password))->toBeTrue();
});
