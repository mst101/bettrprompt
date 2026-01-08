<?php

namespace App\Casts;

use App\Exceptions\EncryptionKeyMissingException;
use App\Services\EncryptionService;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class EncryptedString implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        // If no value or not encrypted, return as-is
        if (! $value || ! ($attributes['is_encrypted'] ?? false)) {
            return $value;
        }

        $encryptionService = app(EncryptionService::class);

        // Get DEK from session
        if (! $encryptionService->hasDekInSession()) {
            // Return placeholder if DEK not available
            // This allows listing encrypted records without decryption
            return '[Encrypted]';
        }

        try {
            $dek = $encryptionService->getDekFromSession();

            return $encryptionService->decrypt($value, $dek);
        } catch (EncryptionKeyMissingException $e) {
            return '[Encrypted]';
        }
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        // If no value, return null
        if (! $value) {
            return $value;
        }

        // Check if the model should be encrypted
        // This looks for a user relationship or auth user with privacy_enabled
        $user = $this->getAssociatedUser($model);

        if (! $user || ! $user->privacy_enabled) {
            return $value;
        }

        $encryptionService = app(EncryptionService::class);

        if (! $encryptionService->hasDekInSession()) {
            throw new EncryptionKeyMissingException('Cannot encrypt data: privacy key not available');
        }

        $dek = $encryptionService->getDekFromSession();

        return $encryptionService->encrypt($value, $dek);
    }

    /**
     * Get the user associated with this model
     */
    private function getAssociatedUser(Model $model): ?object
    {
        // If the model has a user relationship, use it
        if (method_exists($model, 'user') && $model->user) {
            return $model->user;
        }

        // Fall back to authenticated user
        return auth()->user();
    }
}
