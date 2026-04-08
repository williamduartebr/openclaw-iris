<?php

namespace Src\Content\Application\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyN8nWebhookSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.n8n.webhook_secret');

        if (! $secret || $request->header('X-N8N-Webhook-Secret') !== $secret) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
