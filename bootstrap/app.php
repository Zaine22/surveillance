<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
use App\Models\AiModel;
use App\Services\AiHealthService;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'apikey' => \App\Http\Middleware\ApiKeyMiddleware::class,
        ]);
        $middleware->append(\App\Http\Middleware\AllowAllOrigins::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
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
