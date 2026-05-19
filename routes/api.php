<?php

use App\Http\Controllers\Api\AdminStatsController;
use App\Http\Controllers\Api\TestimonialController;
use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OtpController;
use App\Http\Controllers\Api\PropertyMapController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// Public testimonials
Route::get('/testimonials', [TestimonialController::class, 'index']);

// Auth
Route::prefix('auth')->group(function () {
    Route::post('/register',    [AuthController::class, 'register']);
    Route::post('/login',       [AuthController::class, 'login']);
    Route::post('/otp/send',    [OtpController::class, 'send']);
    Route::post('/otp/verify',  [OtpController::class, 'verify']);
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

    // Property Map (Title Verification)
    Route::get('/transactions/{transaction}/property-map',  [PropertyMapController::class, 'show']);
    Route::post('/transactions/{transaction}/property-map', [PropertyMapController::class, 'store']);
    Route::put('/transactions/{transaction}/property-map',  [PropertyMapController::class, 'update']);

    // Public property registry (any authenticated user — sensitive fields stripped)
    Route::get('/property-maps', [PropertyMapController::class, 'publicIndex']);

    // Transaction messages (chat)
    Route::get('/messages/conversations',               [MessageController::class, 'conversations']);
    Route::get('/messages/unread-count',                [MessageController::class, 'unreadCount']);
    Route::get('/transactions/{transaction}/messages',  [MessageController::class, 'index']);
    Route::post('/transactions/{transaction}/messages', [MessageController::class, 'store']);

    // Testimonials (client submit + own status)
    Route::post('/testimonials',      [TestimonialController::class, 'store']);
    Route::get('/testimonials/mine',  [TestimonialController::class, 'mine']);

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
        Route::get('stats',           [AdminStatsController::class, 'stats']);
        Route::get('property-maps',                    [PropertyMapController::class, 'index']);
        Route::delete('property-maps/{propertyMap}',   [PropertyMapController::class, 'destroy']);
        Route::get('analytics',       [AdminStatsController::class, 'analytics']);
        Route::get('testimonials',    [TestimonialController::class, 'adminIndex']);
        Route::put('testimonials/{testimonial}', [TestimonialController::class, 'updateStatus']);
        Route::apiResource('users',         UserController::class);
        Route::apiResource('announcements', AnnouncementController::class)->except(['index']);
    });
});
