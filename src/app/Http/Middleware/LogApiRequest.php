<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequest
{
    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = (string) ($request->headers->get('X-Request-Id') ?: uniqid('req_', true));
        $request->attributes->set('request_id', $requestId);

        $start = microtime(true);
        $response = $next($request);
        $durationMs = (int) round((microtime(true) - $start) * 1000);

        $response->headers->set('X-Request-Id', $requestId);

        Log::info('api.request', [
            'request_id' => $requestId,
            'method' => $request->getMethod(),
            'path' => $request->path(),
            'status' => $response->getStatusCode(),
            'duration_ms' => $durationMs,
            'user_id' => $request->user()?->id,
            'profile' => $request->user()?->profile,
            'ip' => $request->ip(),
        ]);

        return $response;
    }
}
