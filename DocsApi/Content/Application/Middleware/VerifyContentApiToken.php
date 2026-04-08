<?php

namespace Src\Content\Application\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyContentApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $configKey = config('services.content_api.key');

        if (! $configKey || $request->bearerToken() !== $configKey) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}
