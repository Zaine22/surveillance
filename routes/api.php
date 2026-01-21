<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CrawlerTaskItemController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post(
    '/register',
    [AuthController::class, 'register']
);

Route::post(
    '/login',
    [AuthController::class, 'login']
);

Route::post(
    '/send-otp',
    [AuthController::class, 'sendOtp']
);

Route::post(
    '/verify-otp',
    [AuthController::class, 'verifyOtp']
);

Route::middleware('auth:sanctum')->group(
    function () {
        Route::post(
            '/change-password',
            [AuthController::class, 'changePassword']
        );

        Route::post(
            '/logout',
            [AuthController::class, 'logout']
        );

        Route::get(
            '/check-password-expiry',
            [AuthController::class, 'checkPasswordExpiry']
        );

        Route::put(
            '/user',
            [AuthController::class, 'updateUser']
        );
    }
);

Route::post(
    '/crawler/task-items/urls',
    [CrawlerTaskItemController::class, 'store']
);

Route::post(
    '/crawler/task-items/upload',
    [CrawlerTaskItemController::class, 'upload']
);

Route::post(
    '/crawler/trigger',
    [CrawlerTaskItemController::class, 'trigger']
);

Route::get('/crawler/task-items', [
    CrawlerTaskItemController::class,
    'results',
]);
