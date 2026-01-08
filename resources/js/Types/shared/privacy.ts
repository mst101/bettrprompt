/**
 * Privacy status for the current user
 */
export interface PrivacyStatus {
    enabled: boolean;
    canEnable: boolean;
    needsPassword: boolean;
    setupAt: string | null;
}

/**
 * Privacy settings page props
 */
export interface PrivacyPageProps {
    privacy: PrivacyStatus;
    subscription: {
        tier: 'free' | 'pro';
        isPro: boolean;
    };
}

/**
 * Privacy setup page props
 */
export interface PrivacySetupPageProps {
    recoveryPhrase: string;
    step: 'show_phrase' | 'confirm_phrase';
}

/**
 * Privacy unlock page props
 */
export interface PrivacyUnlockPageProps {
    message: string;
}

/**
 * Privacy recovery page props
 */
export interface PrivacyRecoveryPageProps {
    wordList: string[];
}
