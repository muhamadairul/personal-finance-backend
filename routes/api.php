<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BudgetController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\XenditWebhookController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/auth/social', [AuthController::class, 'socialLogin']);

// Xendit Webhook (public — verified via x-callback-token header)
Route::post('/webhooks/xendit/invoice', [XenditWebhookController::class, 'handleInvoice']);

// Protected routes (Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    Route::post('/user/photo', [AuthController::class, 'uploadPhoto']);
    Route::delete('/user/photo', [AuthController::class, 'deletePhoto']);
    Route::post('/user/fcm-token', [AuthController::class, 'updateFcmToken']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // CRUD Resources
    Route::apiResource('transactions', TransactionController::class);
    Route::apiResource('wallets', WalletController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('budgets', BudgetController::class);

    // Reports
    Route::get('/reports/monthly', [ReportController::class, 'monthly']);
    Route::get('/reports/category', [ReportController::class, 'category']);

    // Export (Pro only)
    Route::middleware('pro')->group(function () {
        Route::get('/export/excel', [ExportController::class, 'excel']);
        Route::get('/export/pdf', [ExportController::class, 'pdf']);
    });

    // Subscription
    Route::prefix('subscription')->group(function () {
        Route::get('/plans', [SubscriptionController::class, 'plans']);
        Route::get('/status', [SubscriptionController::class, 'status']);
        Route::post('/pay/qris', [SubscriptionController::class, 'payQris']);
        Route::post('/pay/va', [SubscriptionController::class, 'payVa']);
        Route::post('/pay/ewallet', [SubscriptionController::class, 'payEwallet']);
        Route::get('/check/{id}', [SubscriptionController::class, 'checkStatus']);
        Route::get('/history', [SubscriptionController::class, 'history']);
    });
});
