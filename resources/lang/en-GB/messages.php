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

    // Form validation messages
    'form' => [
        'answer_required' => 'Please provide an answer to the question.',
        'answer_max' => 'The answer must not exceed 1000 characters.',
        'task_description_required' => 'Please describe the task you want to accomplish.',
        'task_description_min' => 'The task description must be at least 10 characters.',
        'experience_level_required' => 'Please select your experience level (Question 1).',
        'usefulness_required' => 'Please rate how useful the app was (Question 2).',
        'usage_intent_required' => 'Please indicate your likelihood to use the app again (Question 3).',
        'desired_features_required' => 'Please select at least one feature you would like to see.',
        'desired_features_other_required' => 'Please describe the feature you selected under "Other".',
        'password_delete_confirmation' => 'Please enter your password to confirm account deletion.',
        'name_required' => 'Please enter your name.',
        'email_required' => 'Please enter your email address.',
        'email_email' => 'Please enter a valid email address.',
        'email_unique' => 'This email address is already registered.',
        'password_required' => 'Please enter a password.',
        'password_confirmed' => 'The password confirmation does not match.',
        'current_password' => 'current password',
        'password' => 'password',
        'password_confirmation' => 'password confirmation',
    ],

    // Prompt Builder messages
    'prompt_builder' => [
        'task_created_failed' => 'Failed to create task. Please try again.',
        'invalid_workflow_stage' => 'Invalid workflow stage for submitting pre-analysis answers.',
        'analysing_task' => 'Analysing your task...',
        'submit_answers_failed' => 'Failed to submit answers. Please try again.',
        'no_quick_queries' => 'This prompt run does not have quick queries to update.',
        'updating_answers' => 'Updating your task with answers...',
        'update_answers_failed' => 'Failed to update answers. Please try again.',
        'cannot_go_back' => 'Cannot go back at this stage.',
        'already_first_question' => 'Already at first question.',
        'go_back_failed' => 'Failed to go back. Please try again.',
        'can_only_edit_completed' => 'Can only edit completed prompt runs.',
        'prompt_updated' => 'Prompt updated successfully.',
        'update_prompt_failed' => 'Failed to update prompt. Please try again.',
        'visitor_limit_reached' => 'You\'ve already created an optimised prompt as a visitor. Please create a free account to continue.',
        'create_prompt_run_failed' => 'An error occurred whilst creating the new prompt run. Please try again.',
        'no_clarifying_questions' => 'Parent prompt run does not have clarifying questions.',
        'generating_optimised_prompt' => 'Generating your optimised prompt with edited answers...',
        'prompt_generation_start_failed' => 'Failed to start prompt generation. Please try again.',
        'switching_framework' => 'Re-analysing with selected framework...',
        'switch_framework_failed' => 'An error occurred whilst switching frameworks. Please try again.',
        'only_failed_runs_can_retry' => 'Only failed runs can be retried.',
        'retrying_pre_analysis' => 'Retrying pre-analysis...',
        'retrying_analysis' => 'Retrying analysis...',
        'retrying_prompt_generation' => 'Retrying prompt generation...',
        'cannot_retry_from_stage' => 'Cannot retry from this stage.',
        'retry_failed' => 'An error occurred whilst retrying. Please try again.',
        'deleted_successfully' => 'Prompt run deleted successfully.',
        'delete_failed' => 'Failed to delete prompt run. Please try again.',
    ],

    // Personality type labels
    'personality_types' => [
        'intj' => 'Architect',
        'intp' => 'Logician',
        'entj' => 'Commander',
        'entp' => 'Debater',
        'infj' => 'Advocate',
        'infp' => 'Mediator',
        'enfj' => 'Protagonist',
        'enfp' => 'Campaigner',
        'istj' => 'Logistician',
        'isfj' => 'Defender',
        'estj' => 'Executive',
        'esfj' => 'Consul',
        'istp' => 'Virtuoso',
        'isfp' => 'Adventurer',
        'estp' => 'Entrepreneur',
        'esfp' => 'Entertainer',
    ],

    // Profile messages
    'profile' => [
        'account_deleted' => 'Your account has been deleted.',
        'update_failed' => 'Failed to update profile. Please try again.',
        'personality_update_failed' => 'Failed to update personality type. Please try again.',
        'location_update_failed' => 'Failed to update location. Please try again.',
        'location_detect_failed' => 'Could not detect location from your IP address. Please set it manually.',
        'location_detection_failed' => 'Failed to detect location. Please try again.',
        'location_clear_failed' => 'Failed to clear location. Please try again.',
        'professional_clear_failed' => 'Failed to clear professional information. Please try again.',
        'team_clear_failed' => 'Failed to clear team information. Please try again.',
        'budget_clear_failed' => 'Failed to clear budget preferences. Please try again.',
        'tools_clear_failed' => 'Failed to clear tools & technologies. Please try again.',
        'professional_update_failed' => 'Failed to update professional context. Please try again.',
        'team_update_failed' => 'Failed to update team context. Please try again.',
        'budget_update_failed' => 'Failed to update budget preferences. Please try again.',
        'tools_update_failed' => 'Failed to update tool preferences. Please try again.',
        'delete_account_failed' => 'Failed to delete account. Please contact support.',
        'unexpected_error' => 'An unexpected error occurred. Please contact support.',
    ],

    // Feedback messages
    'feedback' => [
        'thank_you' => 'Thank you for your feedback!',
        'thank_you_update' => 'Thank you for updating your feedback!',
    ],

    // Subscription messages
    'subscription' => [
        'invalid_plan' => 'Invalid plan selected.',
        'prompt_limit_reached' => 'You have reached your monthly prompt limit.',
        'prompt_limit_reached_upgrade' => 'You have reached your monthly prompt limit. Upgrade to Pro for unlimited prompts.',
        'welcome_pro' => 'Welcome to BettrPrompt Pro!',
        'checkout_cancelled' => 'Subscription checkout was cancelled.',
        'cancelled_pro_until' => 'Your subscription has been cancelled. You will retain Pro access until {date}.',
        'resumed' => 'Your subscription has been resumed.',
    ],

    // Authentication messages
    'auth' => [
        'logged_out' => 'You have been logged out successfully.',
        'logged_out_session' => 'You have been logged out.',
        'admin_required' => 'Unauthorised. Administrator access required.',
        'google_connection_failed' => 'Unable to connect to Google. Please try again later.',
        'google_account_info_failed' => 'Could not retrieve your account information from Google. Please try again.',
        'google_invalid_email' => 'Invalid email address received from Google. Please try again.',
        'account_creation_failed' => 'Failed to create your account. Please try again or contact support.',
        'session_expired' => 'Authentication session expired. Please try logging in again.',
        'google_communication_failed' => 'Failed to communicate with Google. Please try again later.',
        'unexpected_error' => 'An unexpected error occurred. Please try again.',
    ],

    // API responses
    'api' => [
        'unauthorized' => 'Unauthorised',
        'invalid_payload' => 'Invalid payload',
        'prompt_run_not_found' => 'Prompt run not found',
        'database_error' => 'Database error',
        'internal_server_error' => 'Internal server error',
    ],

    // Privacy messages
    'privacy' => [
        'pro_required' => 'You must be a Pro subscriber to enable privacy encryption.',
        'session_expired' => 'Setup session expired. Please start again.',
        'recovery_mismatch' => 'Recovery phrase words do not match.',
        'enabled' => 'Privacy encryption has been enabled. Your data is now protected.',
        'not_enabled' => 'Privacy is not enabled for this account.',
        'unlock_required' => 'Please unlock your privacy key to continue.',
        'unlock_prompt' => 'Please enter your password to unlock your encrypted data.',
        'key_unlocked' => 'Privacy key unlocked.',
        'incorrect_password' => 'Incorrect password.',
        'invalid_format' => 'Invalid recovery phrase format.',
        'recovered' => 'Account recovered successfully. Your password has been updated.',
        'invalid_phrase' => 'Invalid recovery phrase.',
        'key_updated' => 'Privacy key updated with new password.',
        'key_update_failed' => 'Failed to update privacy key.',
        'not_enabled_disable' => 'Privacy is not enabled.',
        'disabled' => 'Privacy encryption has been disabled.',
    ],

    // Workflow service messages
    'workflow' => [
        'invalid_pre_analysis_response' => 'Invalid response from pre-analysis workflow.',
        'missing_clarification_field' => 'Missing needs_clarification field.',
        'proceeding_to_analysis' => 'Proceeding directly to analysis.',
        'analysis_failed' => 'Analysis workflow failed.',
        'analysis_exception' => 'An error occurred whilst analysing the task: {error}',
        'generation_failed' => 'Generation workflow failed.',
        'prompt_generation_exception' => 'An error occurred whilst generating the prompt: {error}',
        'n8n_connection_failed' => 'Failed to connect to n8n: {error}',
        'n8n_request_failed' => 'n8n request failed: {error}',
        'quick_queries_failed' => 'An error occurred whilst generating Quick Queries: {error}',
        'unknown_error' => 'Unknown error',
    ],

    // Admin messages
    'admin' => [
        'task_not_found' => 'Task not found.',
    ],

    // Reference documents
    'reference_documents' => [
        'not_found' => 'Document not found: {filename}',
        'saved' => 'Document \'{filename}\' saved successfully and embedded into workflows',
        'embedded' => 'All {count} documents embedded successfully into workflows',
        'invalid_type' => 'Invalid document type: {type}',
    ],

    // Location messages
    'location' => [
        'unknown' => 'Unknown location',
    ],

    // Voice transcription
    'voice' => [
        'transcription_failed' => 'Failed to transcribe audio. Please try again.',
    ],

    // App metadata
    'app' => [
        'default_title' => 'BettrPrompt',
    ],
];
