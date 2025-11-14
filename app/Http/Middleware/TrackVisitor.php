<?php

namespace App\Http\Middleware;

use App\Models\Visitor;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TrackVisitor
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $visitorId = $request->cookie('visitor_id');
        $isNewVisitor = false;

        Log::info('TrackVisitor middleware executing', [
            'has_cookie' => $visitorId !== null,
            'cookie_value' => $visitorId,
            'url' => $request->fullUrl(),
        ]);

        if ($visitorId) {
            // Existing visitor - update their record
            Log::info('Updating existing visitor', ['visitor_id' => $visitorId]);
            $this->updateVisitor($visitorId);
        } else {
            // New visitor - create record BEFORE processing request
            $visitorId = $this->createVisitor($request);
            $isNewVisitor = true;
            Log::info('Created new visitor', ['visitor_id' => $visitorId]);
        }

        // Make visitor_id available to the request (merge into cookies for this request)
        // This allows controllers to read the visitor_id even for new visitors
        $request->cookies->set('visitor_id', $visitorId);
        Log::info('Set visitor_id in request cookies', ['visitor_id' => $visitorId]);

        // Process the request
        $response = $next($request);

        // Set cookie for new visitors (cookie will be sent with response)
        if ($isNewVisitor) {
            Log::info('Queueing visitor_id cookie', ['visitor_id' => $visitorId]);
            Cookie::queue(
                'visitor_id',
                $visitorId,
                1051200, // 2 years in minutes
                '/',
                null,
                true, // secure (HTTPS only in production)
                true, // httpOnly
                false,
                'lax' // sameSite
            );
        }

        return $response;
    }

    /**
     * Create a new visitor record.
     */
    protected function createVisitor(Request $request): string
    {
        Log::info('Creating visitor record', [
            'url' => $request->fullUrl(),
        ]);

        try {
            // Create visitor in a separate transaction that commits immediately
            // This ensures the visitor persists even if the main request fails
            $visitor = DB::transaction(function () use ($request) {
                return Visitor::create([
                    // Don't set 'id' - let HasUuids trait generate it
                    'utm_source' => $request->query('utm_source'),
                    'utm_medium' => $request->query('utm_medium'),
                    'utm_campaign' => $request->query('utm_campaign'),
                    'utm_term' => $request->query('utm_term'),
                    'utm_content' => $request->query('utm_content'),
                    'referrer' => $request->header('referer'),
                    'landing_page' => $request->fullUrl(),
                    'user_agent' => $request->userAgent(),
                    'ip_address' => $request->ip(),
                    'first_visit_at' => now(),
                    'last_visit_at' => now(),
                    'visit_count' => 1,
                ]);
            });

            $visitorId = (string) $visitor->id;
            Log::info('Visitor record created successfully', ['visitor_id' => $visitorId]);

            return $visitorId;
        } catch (\Exception $e) {
            Log::error('Failed to create visitor record', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Update an existing visitor's last visit time and count.
     */
    protected function updateVisitor(string $visitorId): void
    {
        $visitor = Visitor::find($visitorId);

        if ($visitor) {
            $visitor->update([
                'last_visit_at' => now(),
                'visit_count' => $visitor->visit_count + 1,
            ]);
        }
    }
}
