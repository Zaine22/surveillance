<?php

use App\Http\Controllers\CrawlerTaskItemController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

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
