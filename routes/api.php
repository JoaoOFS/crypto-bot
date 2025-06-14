<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PortfolioController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AssetController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\StrategyController;
use App\Http\Controllers\Api\AlertController;
use App\Http\Controllers\Api\ExchangeController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PushSubscriptionController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\TechnicalAnalysisController;
use App\Http\Controllers\Api\BacktestController;
use App\Http\Controllers\Api\BacktestNotificationController;
use App\Http\Controllers\Api\BacktestComparisonController;
use App\Http\Controllers\Api\BacktestReportController;

Route::prefix('v1')->group(function () {
    // Rotas de autenticação
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });
    });

    Route::apiResource('portfolios', PortfolioController::class);

    // Rotas de assets
    Route::prefix('assets')->group(function () {
        Route::get('/', [AssetController::class, 'index']);
        Route::post('/', [AssetController::class, 'store']);
        Route::get('/{id}', [AssetController::class, 'show']);
        Route::put('/{id}', [AssetController::class, 'update']);
        Route::delete('/{id}', [AssetController::class, 'destroy']);
    });

    // Rotas de transações
    Route::prefix('transactions')->group(function () {
        Route::get('/', [TransactionController::class, 'index']);
        Route::post('/', [TransactionController::class, 'store']);
        Route::get('/{id}', [TransactionController::class, 'show']);
        Route::put('/{id}', [TransactionController::class, 'update']);
        Route::delete('/{id}', [TransactionController::class, 'destroy']);
    });

    // Rotas de estratégias
    Route::prefix('strategies')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [StrategyController::class, 'index']);
        Route::post('/', [StrategyController::class, 'store']);
        Route::get('/{id}', [StrategyController::class, 'show']);
        Route::put('/{id}', [StrategyController::class, 'update']);
        Route::delete('/{id}', [StrategyController::class, 'destroy']);
    });

    // Rotas de alertas
    Route::prefix('alerts')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [AlertController::class, 'index']);
        Route::post('/', [AlertController::class, 'store']);
        Route::get('/{id}', [AlertController::class, 'show']);
        Route::put('/{id}', [AlertController::class, 'update']);
        Route::delete('/{id}', [AlertController::class, 'destroy']);
    });

    // Rotas de exchanges
    Route::prefix('exchanges')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [ExchangeController::class, 'index']);
        Route::post('/', [ExchangeController::class, 'store']);
        Route::get('/{id}', [ExchangeController::class, 'show']);
        Route::put('/{id}', [ExchangeController::class, 'update']);
        Route::delete('/{id}', [ExchangeController::class, 'destroy']);
    });

    // Rotas de notificações
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/notifications/{notification}/mark-as-read', [NotificationController::class, 'markAsRead']);
        Route::post('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
    });

    // Rotas de assinaturas de push
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/push-subscriptions', [PushSubscriptionController::class, 'index']);
        Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store']);
        Route::delete('/push-subscriptions', [PushSubscriptionController::class, 'destroy']);
        Route::patch('/push-subscriptions/{subscription}', [PushSubscriptionController::class, 'update']);
    });

    // Webhook Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('webhooks', WebhookController::class);
        Route::post('webhooks/{id}/regenerate-secret', [WebhookController::class, 'regenerateSecret']);
    });

    // Webhook Endpoints (protegidos por assinatura)
    Route::middleware('webhook.signature')->group(function () {
        Route::post('webhooks/{id}/events', [WebhookController::class, 'handleEvent']);
    });

    // Rotas de análise técnica
    Route::prefix('technical-analysis')->middleware('auth:sanctum')->group(function () {
        Route::get('/{symbol}', [TechnicalAnalysisController::class, 'analyze']);
        Route::get('/{symbol}/indicators', [TechnicalAnalysisController::class, 'getIndicators']);
        Route::post('/backtest', [TechnicalAnalysisController::class, 'backtest']);
    });

    // Backtest routes
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/backtests', [BacktestController::class, 'index']);
        Route::post('/backtests', [BacktestController::class, 'store']);
        Route::get('/backtests/{backtest}', [BacktestController::class, 'show']);
        Route::delete('/backtests/{backtest}', [BacktestController::class, 'destroy']);
        Route::get('/backtests/{backtest}/equity-curve', [BacktestController::class, 'getEquityCurve']);
        Route::get('/backtests/{backtest}/trades', [BacktestController::class, 'getTrades']);
        Route::get('/backtests/{backtest}/performance', [BacktestController::class, 'getPerformance']);
    });

    // Backtest notifications
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/backtest-notifications', [BacktestNotificationController::class, 'index']);
        Route::post('/backtest-notifications/{notification}/read', [BacktestNotificationController::class, 'markAsRead']);
        Route::post('/backtest-notifications/read-all', [BacktestNotificationController::class, 'markAllAsRead']);
        Route::get('/backtest-notifications/unread-count', [BacktestNotificationController::class, 'getUnreadCount']);
        Route::delete('/backtest-notifications/{notification}', [BacktestNotificationController::class, 'destroy']);
    });

    // Backtest exports
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/backtests/{backtest}/export', [BacktestController::class, 'export']);
        Route::get('/backtests/{backtest}/download', [BacktestController::class, 'download']);
        Route::get('/backtests/export-formats', [BacktestController::class, 'getSupportedFormats']);
    });

    // Backtest comparisons
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/backtests/compare', [BacktestComparisonController::class, 'compare']);
        Route::post('/backtests/compare/export', [BacktestComparisonController::class, 'exportComparison']);
        Route::post('/backtests/compare/download', [BacktestComparisonController::class, 'downloadComparison']);
    });

    // Backtest reports
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/backtests/{backtest}/report', [BacktestReportController::class, 'viewReport']);
        Route::post('/backtests/{backtest}/report', [BacktestReportController::class, 'generateReport']);
        Route::post('/backtests/compare/report', [BacktestReportController::class, 'generateComparisonReport']);
        Route::get('/backtests/compare/report', [BacktestReportController::class, 'viewComparisonReport']);
    });
});
