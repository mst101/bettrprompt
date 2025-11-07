<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::post('/n8n/webhook', function (Request $request) {
    // Verify secret
    if ($request->header('X-N8N-SECRET') !== config('services.n8n.webhook_secret')) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    // Handle payload
    Log::info('Received data from n8n', $request->all());

    // Example: update a model
    if ($userId = $request->input('user_id')) {
        User::find($userId)?->update([
            'status' => $request->input('status', 'processed'),
        ]);
    }

    return response()->json(['message' => 'OK']);
});
