<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SingleSessionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // IMPORTANT:
        // If this request has no session, skip this middleware.
        // This prevents: "Session store not set on request"
        if (! $request->hasSession()) {
            return $next($request);
        }

        $user = Auth::guard('web')->user();

        if (! $user) {
            return $next($request);
        }

        $currentSessionId = $request->session()->getId();

        // Check if user has a stored session ID and if it's different from current
        if ($user->current_session_id && $user->current_session_id !== $currentSessionId) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json([
                'message' => '您的帳號已在其他裝置登入，此裝置已被登出。',
                'error' => 'session_conflict',
            ], 401);
        }

        // Update the session ID if it's not set or different
        if ($user->current_session_id !== $currentSessionId) {
            $user->update([
                'current_session_id' => $currentSessionId,
            ]);
        }

        return $next($request);
    }
}
