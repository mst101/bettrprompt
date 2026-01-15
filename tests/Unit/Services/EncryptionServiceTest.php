<?php

use App\Exceptions\EncryptionException;
use App\Services\EncryptionService;

test('encrypts and decrypts data correctly', function () {
    $service = new EncryptionService;
    $dek = $service->generateDek();
    $plaintext = 'sensitive data';

    $encrypted = $service->encrypt($plaintext, $dek);

    expect($encrypted)->not->toBe($plaintext)
        ->and($service->decrypt($encrypted, $dek))->toBe($plaintext);
});

test('different wraps produce different ciphertexts', function () {
    $service = new EncryptionService;
    $dek = $service->generateDek();

    $wrappedOne = $service->wrapDekWithPassword($dek, 'password1');
    $wrappedTwo = $service->wrapDekWithPassword($dek, 'password1');

    expect($wrappedOne)->not->toBe($wrappedTwo)
        ->and($service->unwrapDekWithPassword($wrappedOne, 'password1'))->toBe($dek)
        ->and($service->unwrapDekWithPassword($wrappedTwo, 'password1'))->toBe($dek);
});

test('fails to unwrap with incorrect password', function () {
    $service = new EncryptionService;
    $dek = $service->generateDek();

    $wrapped = $service->wrapDekWithPassword($dek, 'correct-password');

    expect(fn () => $service->unwrapDekWithPassword($wrapped, 'wrong-password'))
        ->toThrow(EncryptionException::class);
});

test('fails to decrypt tampered ciphertext', function () {
    $service = new EncryptionService;
    $dek = $service->generateDek();

    $encrypted = $service->encrypt('data', $dek);
    $decoded = base64_decode($encrypted);
    $tampered = substr_replace($decoded, 'X', 5, 1);

    expect(fn () => $service->decrypt(base64_encode($tampered), $dek))
        ->toThrow(EncryptionException::class);
});

test('encrypts and decrypts arrays', function () {
    $service = new EncryptionService;
    $dek = $service->generateDek();
    $payload = ['key' => 'value', 'nested' => ['count' => 2]];

    $encrypted = $service->encryptArray($payload, $dek);

    expect($service->decryptArray($encrypted, $dek))->toBe($payload);
});
