<?php

namespace Src\Media\Application\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyMediaApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        $expectedToken = config('services.media_api.key');

        if (! $token || ! $expectedToken || $token !== $expectedToken) {
            return response()->json([
                'message' => 'Invalid or missing API token.',
            ], 401);
        }

        return $next($request);
    }
}
