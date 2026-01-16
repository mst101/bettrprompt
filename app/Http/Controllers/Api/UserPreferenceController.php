<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserPreferenceRequest;
use App\Models\Visitor;

class UserPreferenceController extends Controller
{
    public function update(UpdateUserPreferenceRequest $request)
    {
        $validated = $request->validated();

        if ($user = auth()->user()) {
            // Authenticated user - update user preferences
            $user->update($validated);
        } else {
            // Guest visitor - update visitor preferences via cookie
            $visitorId = getVisitorIdFromCookie($request);
            if ($visitorId) {
                $visitor = Visitor::find($visitorId);
                if ($visitor) {
                    $visitor->update($validated);
                }
            }
        }

        return response()->json(['message' => 'Preferences updated successfully']);
    }
}
