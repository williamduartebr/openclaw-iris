<?php

namespace Src\Content\Application\Middleware;

use Closure;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Intercepta 429 do throttle e retorna JSON estruturado
 * com informações úteis para agentes automatizados.
 */
class ContentApiRateLimitResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } catch (ThrottleRequestsException $e) {
            $retryAfter = $e->getHeaders()['Retry-After'] ?? 60;

            return response()->json([
                'message' => 'Too many requests. Please slow down.',
                'retry_after_seconds' => (int) $retryAfter,
                'scope' => 'per-ip',
                'hint' => "Wait {$retryAfter}s before retrying. Read-only endpoints (GET) have higher limits (120/min) than write endpoints (POST/PUT/PATCH/DELETE: 30/min).",
            ], 429, $e->getHeaders());
        }
    }
}
