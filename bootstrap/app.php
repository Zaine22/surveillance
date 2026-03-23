<?php

use App\Http\Middleware\CheckIpWhitelist;
use App\Http\Middleware\OperationLogger;
use App\Models\AiModel;
use App\Services\AiHealthService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => null);
        $middleware->alias([
            'apikey' => \App\Http\Middleware\ApiKeyMiddleware::class,
            'allow.ip' => CheckIpWhitelist::class,
            'operation.log' => OperationLogger::class,
        ]);
        $middleware->append(\App\Http\Middleware\AllowAllOrigins::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn () => true);
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
