<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('x-api-key');

        // store your key in .env
        if (! $apiKey || $apiKey !== config('app.api_key')) {
            return response()->json([
                'status' => 'error',
                'code' => 'Unauthorized',
                'message' => 'API Key 無效或遺失',
            ], 401);
        }

        return $next($request);
    }
}
