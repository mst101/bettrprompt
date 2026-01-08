<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Custom Application Messages
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for custom application messages
    | including success/error notifications, email subjects and content.
    |
    */

    // Success messages
    'profile_updated' => 'Your profile has been updated successfully.',
    'profile_deleted' => 'Your profile has been deleted.',
    'password_changed' => 'Your password has been changed successfully.',
    'email_updated' => 'Your email address has been updated.',
    'preferences_saved' => 'Your preferences have been saved.',
    'settings_updated' => 'Your settings have been updated.',

    // Error messages
    'something_went_wrong' => 'Something went wrong. Please try again.',
    'unauthorized' => 'You are not authorised to perform this action.',
    'not_found' => 'The requested resource was not found.',
    'workflow_failed' => 'The workflow failed to process. Please try again.',
    'rate_limited' => 'Too many requests. Please wait a moment before trying again.',
    'server_error' => 'A server error occurred. Please try again later.',
    'invalid_request' => 'The request is invalid.',
    'validation_failed' => 'The provided data failed validation.',

    // Workflow messages
    'prompt_generating' => 'Generating your prompt...',
    'prompt_generated' => 'Your prompt has been generated successfully.',
    'prompt_generation_failed' => 'Failed to generate prompt. Please try again.',
    'analysis_in_progress' => 'Your analysis is in progress. Please wait...',
    'analysis_complete' => 'Your analysis is complete.',

    // Email messages
    'email' => [
        'password_reset_subject' => 'Reset Your Password',
        'password_reset_title' => 'Reset Your Password',
        'password_reset_body' => 'You are receiving this email because we received a password reset request for your account.',
        'password_reset_button' => 'Reset Password',
        'password_reset_footer' => 'This password reset link will expire in {minutes} minutes.',
        'password_reset_footer_no_action' => 'If you did not request a password reset, no further action is required.',

        'welcome_subject' => 'Welcome to BettrPrompt',
        'welcome_title' => 'Welcome to BettrPrompt',
        'welcome_body' => 'Thank you for joining BettrPrompt! We are excited to help you create personality-calibrated AI prompts.',
        'welcome_footer' => 'If you have any questions, please do not hesitate to contact us.',

        'email_verification_subject' => 'Verify Your Email Address',
        'email_verification_title' => 'Verify Your Email Address',
        'email_verification_body' => 'Please verify your email address by clicking the button below.',
        'email_verification_button' => 'Verify Email',
        'email_verification_footer' => 'If you did not create this account, you can ignore this email.',

        'account_suspended_subject' => 'Your Account Has Been Suspended',
        'account_suspended_title' => 'Account Suspended',
        'account_suspended_body' => 'Your account has been suspended. Please contact support for more information.',

        'password_changed_subject' => 'Your Password Has Been Changed',
        'password_changed_title' => 'Password Changed',
        'password_changed_body' => 'Your password has been changed successfully. If you did not make this change, please contact support immediately.',

        'greeting' => 'Hello {name}!',
        'regards' => 'Best regards,',
        'regards_team' => 'The BettrPrompt Team',
    ],

    // Validation-related messages
    'invalid_email' => 'Please enter a valid email address.',
    'email_required' => 'Please provide your email address.',
    'password_required' => 'A password is required.',
    'password_too_short' => 'Your password must be at least 8 characters.',
    'passwords_do_not_match' => 'The passwords do not match.',
    'name_required' => 'Please provide your name.',

    // Account-related
    'account_created' => 'Your account has been created successfully.',
    'account_already_exists' => 'An account with this email already exists.',
    'email_not_verified' => 'Please verify your email address before proceeding.',
    'please_login' => 'Please log in to continue.',
    'session_expired' => 'Your session has expired. Please log in again.',

    // Data deletion
    'data_deletion_requested' => 'Your data deletion request has been submitted.',
    'data_will_be_deleted' => 'Your data will be permanently deleted in 30 days.',
    'data_deletion_cancelled' => 'Your data deletion request has been cancelled.',
];
