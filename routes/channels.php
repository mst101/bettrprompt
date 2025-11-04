<?php

use App\Models\PromptRun;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/*
 * Prompt Run Channel
 *
 * Currently using public channels for simplicity.
 * To make private: change Channel to PrivateChannel in events and uncomment below.
 */
// Broadcast::channel('prompt-run.{promptRunId}', function ($user, $promptRunId) {
//     $promptRun = PromptRun::find($promptRunId);
//     return $user && $promptRun && $user->id === $promptRun->user_id;
// });
