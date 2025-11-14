<?php

namespace App\Http\Middleware;

use App\Models\Visitor;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
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

        if ($visitorId) {
            // Existing visitor - update their record
            $this->updateVisitor($visitorId);
        } else {
            // New visitor - create record BEFORE processing request
            $visitorId = $this->createVisitor($request);
            $isNewVisitor = true;
        }

        // Make visitor_id available to the request (merge into cookies for this request)
        // This allows controllers to read the visitor_id even for new visitors
        $request->cookies->set('visitor_id', $visitorId);

        // Process the request
        $response = $next($request);

        // Set cookie for new visitors (cookie will be sent with response)
        if ($isNewVisitor) {
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
        $visitorId = (string) Str::uuid();

        Visitor::create([
            'id' => $visitorId,
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

        return $visitorId;
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
