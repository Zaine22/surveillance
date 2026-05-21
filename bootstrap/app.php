<?php

use App\Http\Middleware\CheckIpWhitelist;
use App\Http\Middleware\OperationLogger;
use App\Models\AiModel;
use App\Services\AiHealthService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn() => null);
        $middleware->alias([
            'apikey'        => \App\Http\Middleware\ApiKeyMiddleware::class,
            'allow.ip'      => CheckIpWhitelist::class,
            'operation.log' => OperationLogger::class,
        ]);
        $middleware->append(\App\Http\Middleware\AllowAllOrigins::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn() => true);
        $exceptions->render(function (Throwable $e, Request $request) {
            $statusCode = $e instanceof HttpExceptionInterface
                ? $e->getStatusCode()
                : 500;

            if ($statusCode >= 500) {
                return response()->json([
                    'message' => '伺服器錯誤，請稍後再試。',
                ], 500);
            }

            return null;
        });
    })

    ->withSchedule(function (Schedule $schedule) {

        $schedule->call(function () {

            $models = AiModel::where('status', 'enabled')->get();

            $service = app(AiHealthService::class);

            foreach ($models as $model) {
                $service->check($model);
            }

        })->everyMinute();

    })->create();
