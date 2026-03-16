<?php
namespace App\Http\Middleware;

use App\Models\OperationLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OperationLogger
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        if (auth()->check()) {
            $user          = auth()->user();
            $executionTime = round((microtime(true) - $startTime) * 1000);
            OperationLog::create([
                'user_id'         => $user->id,
                'operator_name'   => $user->name,
                'operator_email'  => $user->email,
                'department'      => $user->department,
                'role'            => $user->roles,
                'page_url'        => preg_replace('/^api\//', '', $request->path()),
                'action'          => match ($request->method()) {
                    'POST'   => 'create',
                    'PUT', 'PATCH' => 'update',
                    'DELETE' => 'delete',
                    default  => 'view'
                },
                'status'          => $response->getStatusCode() < 400
                    ? 'success'
                    : 'failed',
                'ip_address'      => $request->ip(),
                'token'           => $request->bearerToken(),
                'cost_time'       => $executionTime,
                'operation_time'  => now(),
                'request_payload' => [
                    'method'     => $request->method(),
                    'url'        => $request->fullUrl(),
                    'user_agent' => $request->userAgent(),
                    'data'       => $request->except([
                        'password',
                        'token',
                        'otp',
                    ]),
                ],
            ]);
        }

        return $response;
    }
}
