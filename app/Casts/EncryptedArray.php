<?php

namespace App\Casts;

use App\Exceptions\EncryptionKeyMissingException;
use App\Services\EncryptionService;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class EncryptedArray implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        // If no value or not encrypted, decode normally
        if (! $value || ! ($attributes['is_encrypted'] ?? false)) {
            return is_string($value) ? json_decode($value, true) : $value;
        }

        $encryptionService = app(EncryptionService::class);

        // Get DEK from session
        if (! $encryptionService->hasDekInSession()) {
            // Return placeholder if DEK not available
            return null;
        }

        try {
            $dek = $encryptionService->getDekFromSession();

            return $encryptionService->decryptArray($value, $dek);
        } catch (EncryptionKeyMissingException $e) {
            return null;
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
            return null;
        }

        // Check if the model should be encrypted
        $user = $this->getAssociatedUser($model);

        if (! $user || ! $user->privacy_enabled) {
            return json_encode($value);
        }

        $encryptionService = app(EncryptionService::class);

        if (! $encryptionService->hasDekInSession()) {
            throw new EncryptionKeyMissingException('Cannot encrypt data: privacy key not available');
        }

        $dek = $encryptionService->getDekFromSession();

        return $encryptionService->encryptArray($value, $dek);
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
