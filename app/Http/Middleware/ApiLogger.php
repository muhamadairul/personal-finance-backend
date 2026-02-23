<?php

namespace App\Http\Middleware;

use App\Models\ApiLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiLogger
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Record the start time for duration calculation
        $request->attributes->set('api_log_start_time', microtime(true));

        return $next($request);
    }

    /**
     * Handle tasks after the response has been sent to the browser.
     */
    public function terminate(Request $request, Response $response): void
    {
        $startTime = $request->attributes->get('api_log_start_time');
        $duration  = $startTime ? round((microtime(true) - $startTime) * 1000, 2) : null;

        // Truncate response body to max 2KB to prevent DB bloat
        $responseContent = $response->getContent();
        if (strlen($responseContent) > 2048) {
            $responseContent = substr($responseContent, 0, 2048) . '... [truncated]';
        }

        try {
            ApiLog::create([
                'user_id'    => auth()->id(),
                'method'     => $request->method(),
                'url'        => $request->fullUrl(),
                'payload'    => $request->all() ?: null,
                'response'   => json_decode($responseContent, true),
                'status_code' => $response->getStatusCode(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'duration_ms' => $duration,
            ]);
        } catch (\Throwable $e) {
            // Silently fail — logging should never break the app
            logger()->error('ApiLogger failed: ' . $e->getMessage());
        }
    }
}
