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
        $response = $next($request);

        if (auth()->check()) {

            OperationLog::create([
                'user_id'       => auth()->id(),
                'operator_name' => auth()->user()->name,
                'department'    => auth()->user()->department ?? null,
                'page_url'      => $request->path(),
                'action'        => $request->method(),
                'status'        => $response->status() == 200 ? 'success' : 'failed',
                'ip_address'    => $request->ip(),
            ]);

        }

        return $response;
    }
}
