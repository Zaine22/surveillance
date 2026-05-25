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
        $user = Auth::user();

        if ($user) {
            $currentSessionId = session()->getId();

            // Check if user has a stored session ID and if it's different from current
            if ($user->current_session_id && $user->current_session_id !== $currentSessionId) {
                // Another session exists, logout the user
                Auth::guard('web')->logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return response()->json([
                    'message' => '您的帳號已在其他裝置登入，此裝置已被登出。',
                    'error' => 'session_conflict'
                ], 401);
            }

            // Update the session ID if it's not set or different
            if ($user->current_session_id !== $currentSessionId) {
                $user->update(['current_session_id' => $currentSessionId]);
            }
        }

        return $next($request);
    }
}