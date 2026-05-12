<?php

use App\Http\Controllers\Api\AdminStatsController;
use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// Auth
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout',          [AuthController::class, 'logout']);
    Route::get('/auth/me',               [AuthController::class, 'me']);
    Route::put('/auth/profile',          [AuthController::class, 'updateProfile']);
    Route::post('/auth/avatar',          [AuthController::class, 'updateAvatar']);

    // Transactions (role-filtered inside controller)
    Route::apiResource('transactions', TransactionController::class);

    // Documents
    Route::post('/transactions/{transaction}/documents', [DocumentController::class, 'store']);
    Route::delete('/documents/{document}',               [DocumentController::class, 'destroy']);

    // Staff list for assignment
    Route::get('/staff', [UserController::class, 'staff']);

    // Notifications
    Route::get('/notifications',              [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::put('/notifications/read-all',     [NotificationController::class, 'markAllRead']);
    Route::put('/notifications/{notification}/read', [NotificationController::class, 'markRead']);

    // Announcements (published only for non-admin)
    Route::get('/announcements', [AnnouncementController::class, 'index']);

    // Admin routes
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('stats', [AdminStatsController::class, 'stats']);
        Route::apiResource('users',         UserController::class);
        Route::apiResource('announcements', AnnouncementController::class)->except(['index']);
    });
});
