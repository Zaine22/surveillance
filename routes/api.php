<?php

use App\Http\Controllers\AiModelController;
use App\Http\Controllers\AiModelTaskController;
use App\Http\Controllers\AiPredictResultController;
use App\Http\Controllers\AiPredictResultItemController;
use App\Http\Controllers\AuditRatioController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BotMachineController;
use App\Http\Controllers\CaseManagementController;
use App\Http\Controllers\CaseManagementItemController;
use App\Http\Controllers\CrawlerConfigController;
use App\Http\Controllers\CrawlerTaskController;
use App\Http\Controllers\CrawlerTaskItemController;
use App\Http\Controllers\DataSyncRecordController;
use App\Http\Controllers\GlobalWhitelistController;
use App\Http\Controllers\LexiconController;
use App\Http\Controllers\LexiconKeywordController;
use App\Http\Controllers\NotifyTemplateController;
use App\Http\Controllers\SystemDataController;
use App\Http\Controllers\SystemNoticeController;
use App\Http\Controllers\ValidationRecordController;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Request;

Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);

    Route::apiResource('ai-models', AiModelController::class);
    Route::apiResource('ai-model-tasks', AiModelTaskController::class);
    Route::apiResource('ai-predict-results', AiPredictResultController::class);
    Route::apiResource('ai-predict-result-items', AiPredictResultItemController::class);
    Route::apiResource('audit-ratios', AuditRatioController::class);
    Route::apiResource('bot-machines', BotMachineController::class);
    Route::apiResource('case-management', CaseManagementController::class);
    Route::apiResource('case-management-items', CaseManagementItemController::class);
    Route::apiResource('crawler-configs', CrawlerConfigController::class);
    Route::apiResource('crawler-tasks', CrawlerTaskController::class);
    Route::apiResource('crawler-task-items', CrawlerTaskItemController::class);
    Route::apiResource('data-sync-records', DataSyncRecordController::class);
    Route::apiResource('global-whitelists', GlobalWhitelistController::class);
    Route::apiResource('lexicons', LexiconController::class);
    Route::apiResource('lexicon-keywords', LexiconKeywordController::class);
    Route::apiResource('notify-templates', NotifyTemplateController::class);
    Route::apiResource('system-data', SystemDataController::class);
    Route::apiResource('system-notices', SystemNoticeController::class);
    Route::apiResource('validation-records', ValidationRecordController::class);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);

Route::post('/login', [AuthController::class, 'login']);

Route::post('/send-otp', [AuthController::class, 'sendOtp']);

Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::post('/logout', [AuthController::class, 'logout']);
}
);

Route::post('/crawler/task-items/urls', [CrawlerTaskItemController::class, 'store']);

Route::post('/crawler/task-items/upload', [CrawlerTaskItemController::class, 'upload']);

Route::post('/crawler/trigger', [CrawlerTaskItemController::class, 'trigger']);

Route::get('/crawler/task-items', [CrawlerTaskItemController::class, 'results']);
