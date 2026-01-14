<?php

namespace App\Http\Controllers;

use App\Services\EncryptionService;
use App\Services\RecoveryPhraseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class PrivacyController extends Controller
{
    public function __construct(
        private EncryptionService $encryptionService,
        private RecoveryPhraseService $recoveryPhraseService,
    ) {}

    /**
     * Show privacy settings page
     */
    public function show(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Settings/Privacy', [
            'privacy' => $user->getPrivacyStatus(),
            'subscription' => $user->getSubscriptionStatus(),
        ]);
    }

    /**
     * Begin privacy setup - generate recovery phrase
     */
    public function beginSetup(Request $request)
    {
        $user = $request->user();

        if (! $user->canEnablePrivacy()) {
            return back()->withErrors(['privacy' => 'You must be a Pro subscriber to enable privacy encryption.']);
        }

        // Generate DEK and recovery phrase
        $dek = $this->encryptionService->generateDek();
        $recoveryPhrase = $this->recoveryPhraseService->generate(12);

        // Store temporarily in session for confirmation step
        session([
            'privacy_setup.dek' => encrypt($dek),
            'privacy_setup.recovery_phrase' => $recoveryPhrase,
        ]);

        return Inertia::render('Settings/PrivacySetup', [
            'recoveryPhrase' => $recoveryPhrase,
            'step' => 'show_phrase',
        ]);
    }

    /**
     * Confirm recovery phrase and complete setup
     */
    public function confirmSetup(Request $request)
    {
        $request->validate([
            'confirmation_words' => 'required|array|min:3',
            'password' => 'required|string|current_password',
        ]);

        $user = $request->user();

        // Get temporary setup data
        $encryptedDek = session('privacy_setup.dek');
        $recoveryPhrase = session('privacy_setup.recovery_phrase');

        if (! $encryptedDek || ! $recoveryPhrase) {
            return back()->withErrors(['setup' => __('messages.privacy.session_expired')]);
        }

        $dek = decrypt($encryptedDek);

        // Verify confirmation words (check 3 random words from phrase)
        $words = explode(' ', $recoveryPhrase);
        foreach ($request->confirmation_words as $index => $word) {
            if (strtolower(trim($word)) !== $words[$index]) {
                return back()->withErrors(['confirmation_words' => __('messages.privacy.recovery_mismatch')]);
            }
        }

        // Wrap DEK with password and recovery phrase
        $encryptedDekWithPassword = $this->encryptionService->wrapDekWithPassword($dek, $request->password);
        $encryptedDekWithRecovery = $this->encryptionService->wrapDekWithRecovery($dek, $recoveryPhrase);

        // Save to user
        $user->update([
            'privacy_enabled' => true,
            'encrypted_dek' => $encryptedDekWithPassword,
            'recovery_dek' => $encryptedDekWithRecovery,
            'dek_created_at' => now(),
        ]);

        // Store DEK in session for immediate use
        $this->encryptionService->storeDekInSession($dek);

        // Clear setup session
        session()->forget(['privacy_setup.dek', 'privacy_setup.recovery_phrase']);

        return redirect()->route('settings.privacy', ['country' => request()->route('country')])
            ->with('success', __('messages.privacy.enabled'));
    }

    /**
     * Unlock privacy key with password (on login or session start)
     */
    public function unlock(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = $request->user();

        if (! $user->hasPrivacyEnabled()) {
            return back()->withErrors(['privacy' => 'Privacy is not enabled for this account.']);
        }

        try {
            $dek = $this->encryptionService->unwrapDekWithPassword(
                $user->encrypted_dek,
                $request->password
            );

            $this->encryptionService->storeDekInSession($dek);

            return back()->with('success', __('messages.privacy.key_unlocked'));
        } catch (\Exception $e) {
            return back()->withErrors(['password' => __('messages.privacy.incorrect_password')]);
        }
    }

    /**
     * Show recovery form
     */
    public function showRecovery(): Response
    {
        return Inertia::render('Auth/PrivacyRecovery', [
            'wordList' => $this->recoveryPhraseService->getWordList(),
        ]);
    }

    /**
     * Recover privacy key using recovery phrase
     */
    public function recover(Request $request)
    {
        $request->validate([
            'recovery_phrase' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (! $user->hasPrivacyEnabled()) {
            return back()->withErrors(['privacy' => 'Privacy is not enabled for this account.']);
        }

        if (! $this->recoveryPhraseService->validate($request->recovery_phrase)) {
            return back()->withErrors(['recovery_phrase' => __('messages.privacy.invalid_format')]);
        }

        try {
            // Unwrap DEK with recovery phrase
            $dek = $this->encryptionService->unwrapDekWithRecovery(
                $user->recovery_dek,
                $request->recovery_phrase
            );

            // Re-wrap DEK with new password
            $newEncryptedDek = $this->encryptionService->wrapDekWithPassword($dek, $request->new_password);

            // Update user password and encrypted DEK
            $user->update([
                'password' => Hash::make($request->new_password),
                'encrypted_dek' => $newEncryptedDek,
            ]);

            // Store DEK in session
            $this->encryptionService->storeDekInSession($dek);

            return redirect()->route('settings.privacy', ['country' => request()->route('country')])
                ->with('success', __('messages.privacy.recovered'));
        } catch (\Exception $e) {
            return back()->withErrors(['recovery_phrase' => __('messages.privacy.invalid_phrase')]);
        }
    }

    /**
     * Re-wrap DEK when password changes
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string|current_password',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (! $user->hasPrivacyEnabled()) {
            // No privacy enabled, just update password normally
            return redirect()->route('profile.edit', ['country' => request()->route('country')]);
        }

        try {
            // Unwrap DEK with current password
            $dek = $this->encryptionService->unwrapDekWithPassword(
                $user->encrypted_dek,
                $request->current_password
            );

            // Re-wrap DEK with new password
            $newEncryptedDek = $this->encryptionService->wrapDekWithPassword($dek, $request->new_password);

            // Update encrypted DEK
            $user->update([
                'encrypted_dek' => $newEncryptedDek,
            ]);

            return back()->with('success', __('messages.privacy.key_updated'));
        } catch (\Exception $e) {
            return back()->withErrors(['current_password' => __('messages.privacy.key_update_failed')]);
        }
    }

    /**
     * Disable privacy (decrypt all data)
     */
    public function disable(Request $request)
    {
        $request->validate([
            'password' => 'required|string|current_password',
            'confirm' => 'required|accepted',
        ]);

        $user = $request->user();

        if (! $user->hasPrivacyEnabled()) {
            return back()->withErrors(['privacy' => __('messages.privacy.not_enabled_disable')]);
        }

        // TODO: Queue job to decrypt all user's prompt runs
        // For now, just disable the flag
        $user->update([
            'privacy_enabled' => false,
            'encrypted_dek' => null,
            'recovery_dek' => null,
            'dek_created_at' => null,
        ]);

        // Clear DEK from session
        $this->encryptionService->clearDekFromSession();

        return redirect()->route('settings.privacy', ['country' => request()->route('country')])
            ->with('success', __('messages.privacy.disabled'));
    }
}
