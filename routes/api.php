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

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/send-otp', [AuthController::class, 'sendOtp']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
    Route::get('/check-password-expiry', [AuthController::class, 'checkPasswordExpiry']);
    Route::put('/user', [AuthController::class, 'updateUser']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::get('/users', [AuthController::class, 'index']);

    Route::post('/import/lexicon-keywords', [LexiconKeywordController::class, 'import']);
    Route::post('/export/lexicon-keywords/{lexiconId}', [LexiconKeywordController::class, 'export']);
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

Route::middleware('apikey')->group(function () {
    Route::post('/caseFeedback', [CaseManagementController::class, 'netChineseCaseFeedback']);
    Route::post('/newcaseCreate', [CaseManagementController::class, 'externalCaseCreate']);
    Route::post('/caseScreenshot', [CaseManagementController::class, 'netChineseCaseScreenshot']);
});

Route::post('/crawler/task-items/urls', [CrawlerTaskItemController::class, 'store']);
Route::post('/crawler/task-items/upload', [CrawlerTaskItemController::class, 'upload']);
Route::post('/crawler/trigger', [CrawlerTaskItemController::class, 'trigger']);
Route::get('/crawler/task-items', [CrawlerTaskItemController::class, 'results']);
