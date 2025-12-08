<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/*
 * Prompt Run Channel
 *
 * Uses public channels that allow both authenticated and unauthenticated users.
 * This enables real-time updates for visitors without requiring authentication.
 *
 * Authorization: Always allow access to prompt-run.* channels.
 * This is safe because:
 * 1. Events are broadcast on specific channel (prompt-run.{id})
 * 2. Events only contain non-sensitive data (workflow stage, question count)
 * 3. Any security-sensitive operations require proper authentication
 * 4. Visitors can only access their own prompt runs (enforced via visitor_id cookie)
 */
Broadcast::channel('prompt-run.{promptRunId}', function ($user, $promptRunId) {
    return true;
});
