<?php

namespace App\Services;

use App\Exceptions\EncryptionException;
use App\Exceptions\EncryptionKeyMissingException;
use Exception;

class EncryptionService
{
    private const CIPHER = 'aes-256-gcm';

    private const KEY_LENGTH = 32; // 256 bits

    private const NONCE_LENGTH = 12; // 96 bits for GCM

    private const TAG_LENGTH = 16; // 128 bits

    private const PBKDF2_ITERATIONS = 100000;

    private const PBKDF2_ALGO = 'sha256';

    /**
     * Generate a new Data Encryption Key (DEK)
     */
    public function generateDek(): string
    {
        return random_bytes(self::KEY_LENGTH);
    }

    /**
     * Derive a key from a password using PBKDF2
     */
    public function deriveKeyFromPassword(string $password, string $salt): string
    {
        return hash_pbkdf2(
            self::PBKDF2_ALGO,
            $password,
            $salt,
            self::PBKDF2_ITERATIONS,
            self::KEY_LENGTH,
            true
        );
    }

    /**
     * Wrap (encrypt) DEK with a password-derived key
     *
     * @return string Base64-encoded wrapped DEK (salt + nonce + ciphertext + tag)
     */
    public function wrapDekWithPassword(string $dek, string $password): string
    {
        $salt = random_bytes(16);
        $derivedKey = $this->deriveKeyFromPassword($password, $salt);

        $nonce = random_bytes(self::NONCE_LENGTH);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $dek,
            self::CIPHER,
            $derivedKey,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            '',
            self::TAG_LENGTH
        );

        if ($ciphertext === false) {
            throw new EncryptionException('Failed to wrap DEK');
        }

        return base64_encode($salt.$nonce.$ciphertext.$tag);
    }

    /**
     * Unwrap (decrypt) DEK using password
     *
     * @return string The raw DEK bytes
     *
     * @throws EncryptionException If decryption fails (wrong password)
     */
    public function unwrapDekWithPassword(string $wrappedDek, string $password): string
    {
        $data = base64_decode($wrappedDek, true);
        if ($data === false) {
            throw new EncryptionException('Invalid wrapped DEK format');
        }

        $salt = substr($data, 0, 16);
        $nonce = substr($data, 16, self::NONCE_LENGTH);
        $ciphertext = substr($data, 28, -self::TAG_LENGTH);
        $tag = substr($data, -self::TAG_LENGTH);

        $derivedKey = $this->deriveKeyFromPassword($password, $salt);

        $dek = openssl_decrypt(
            $ciphertext,
            self::CIPHER,
            $derivedKey,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag
        );

        if ($dek === false) {
            throw new EncryptionException('Failed to unwrap DEK - incorrect password');
        }

        return $dek;
    }

    /**
     * Wrap DEK with recovery phrase
     */
    public function wrapDekWithRecovery(string $dek, string $recoveryPhrase): string
    {
        // Normalise recovery phrase (lowercase, trim, single spaces)
        $normalised = strtolower(trim(preg_replace('/\s+/', ' ', $recoveryPhrase)));

        return $this->wrapDekWithPassword($dek, $normalised);
    }

    /**
     * Unwrap DEK using recovery phrase
     */
    public function unwrapDekWithRecovery(string $wrappedDek, string $recoveryPhrase): string
    {
        $normalised = strtolower(trim(preg_replace('/\s+/', ' ', $recoveryPhrase)));

        return $this->unwrapDekWithPassword($wrappedDek, $normalised);
    }

    /**
     * Encrypt plaintext data using DEK
     *
     * @return string Base64-encoded encrypted data (nonce + ciphertext + tag)
     */
    public function encrypt(string $plaintext, string $dek): string
    {
        $nonce = random_bytes(self::NONCE_LENGTH);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $plaintext,
            self::CIPHER,
            $dek,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            '',
            self::TAG_LENGTH
        );

        if ($ciphertext === false) {
            throw new EncryptionException('Failed to encrypt data');
        }

        return base64_encode($nonce.$ciphertext.$tag);
    }

    /**
     * Decrypt ciphertext using DEK
     *
     * @return string The decrypted plaintext
     *
     * @throws EncryptionException If decryption fails
     */
    public function decrypt(string $encryptedData, string $dek): string
    {
        $data = base64_decode($encryptedData, true);
        if ($data === false) {
            throw new EncryptionException('Invalid encrypted data format');
        }

        $nonce = substr($data, 0, self::NONCE_LENGTH);
        $ciphertext = substr($data, self::NONCE_LENGTH, -self::TAG_LENGTH);
        $tag = substr($data, -self::TAG_LENGTH);

        $plaintext = openssl_decrypt(
            $ciphertext,
            self::CIPHER,
            $dek,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag
        );

        if ($plaintext === false) {
            throw new EncryptionException('Failed to decrypt data');
        }

        return $plaintext;
    }

    /**
     * Encrypt array/JSON data
     */
    public function encryptArray(array $data, string $dek): string
    {
        return $this->encrypt(json_encode($data), $dek);
    }

    /**
     * Decrypt to array
     */
    public function decryptArray(string $encryptedData, string $dek): array
    {
        $json = $this->decrypt($encryptedData, $dek);
        $decoded = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new EncryptionException('Decrypted data is not valid JSON');
        }

        return $decoded;
    }

    /**
     * Store DEK in session (encrypted with session key)
     */
    public function storeDekInSession(string $dek): void
    {
        session(['privacy.dek' => encrypt($dek)]);
    }

    /**
     * Retrieve DEK from session
     *
     * @throws EncryptionKeyMissingException If DEK not in session
     */
    public function getDekFromSession(): string
    {
        $encryptedDek = session('privacy.dek');

        if (! $encryptedDek) {
            throw new EncryptionKeyMissingException('Privacy key not found in session');
        }

        try {
            return decrypt($encryptedDek);
        } catch (Exception $e) {
            throw new EncryptionKeyMissingException('Failed to decrypt session key: '.$e->getMessage());
        }
    }

    /**
     * Check if DEK is available in session
     */
    public function hasDekInSession(): bool
    {
        return session()->has('privacy.dek');
    }

    /**
     * Clear DEK from session
     */
    public function clearDekFromSession(): void
    {
        session()->forget('privacy.dek');
    }
}
