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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/auth/social', [AuthController::class, 'socialLogin']);

// Forgot Password routes
Route::post('/password/email', [\App\Http\Controllers\Api\ForgotPasswordController::class, 'sendOtp']);
Route::post('/password/verify-otp', [\App\Http\Controllers\Api\ForgotPasswordController::class, 'verifyOtp']);
Route::post('/password/reset', [\App\Http\Controllers\Api\ForgotPasswordController::class, 'reset']);

// Vercel Cron trigger (secured by secret key)
Route::get('/cron/trigger', function (Request $request) {
    $secret = config('app.cron_secret');
    if (!$secret || $request->query('key') !== $secret) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }
    \Illuminate\Support\Facades\Artisan::call('schedule:run');
    return response()->json([
        'message' => 'Schedule executed',
        'output'  => \Illuminate\Support\Facades\Artisan::output(),
    ]);
});

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

    // Notifications
    Route::get('/notifications', [\App\Http\Controllers\Api\NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [\App\Http\Controllers\Api\NotificationController::class, 'unreadCount']);
    Route::post('/notifications/read-all', [\App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);

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
