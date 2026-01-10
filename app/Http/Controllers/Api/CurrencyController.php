<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CurrencyController extends Controller
{
    /**
     * Update user's currency preference
     *
     * Supports both authenticated users and visitors.
     * For visitors, stores in session. For authenticated users, updates database.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'currency_code' => [
                'required',
                'string',
                'size:3',
                Rule::in(['GBP', 'EUR', 'USD']),
            ],
        ]);

        if ($request->user()) {
            // Authenticated user: update database
            $request->user()->update([
                'currency_code' => $validated['currency_code'],
            ]);
        } else {
            // Visitor: update session (Visitor model update handled by middleware if needed)
            session(['currency_code' => $validated['currency_code']]);
        }

        return response()->json([
            'success' => true,
            'currency_code' => $validated['currency_code'],
        ]);
    }
}
