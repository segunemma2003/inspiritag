<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\BusinessAccountController;
use App\Http\Controllers\Api\DebugController;
use App\Http\Controllers\Api\NotificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/verify-firebase-token', [AuthController::class, 'verifyFirebaseToken']);
Route::get('/interests', [UserController::class, 'getInterests']); // Public interests list
Route::get('/categories', [CategoryController::class, 'index']); // Public categories list
// routes/api.php
Route::get('/debug/s3-config', [DebugController::class, 'checkS3Config']);
Route::get('/debug/test-presigned', [DebugController::class, 'testPresignedUrl']);
// Protected routes
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::delete('/delete-account', [AuthController::class, 'deleteAccount']);

    // User routes
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::put('/users/profile', [UserController::class, 'updateProfile']);
    Route::post('/users/{user}/follow', [UserController::class, 'follow']);
    Route::delete('/users/{user}/unfollow', [UserController::class, 'unfollow']);
    Route::get('/users/{user}/followers', [UserController::class, 'followers']);
    Route::get('/users/{user}/following', [UserController::class, 'following']);

    // User search routes
    Route::post('/users/search/interests', [UserController::class, 'searchByInterests']);
    Route::post('/users/search/profession', [UserController::class, 'searchByProfession']);

    // Post routes
    Route::get('/posts', [PostController::class, 'index']);
    Route::post('/posts', [PostController::class, 'store']);

    // User's saved and liked posts - using different route structure
    Route::get('/user-saved-posts', [PostController::class, 'getSavedPosts']);
    Route::get('/user-liked-posts', [PostController::class, 'getLikedPosts']);

    // Alternative routes for saved and liked posts
    Route::get('/saved-posts', [PostController::class, 'getSavedPosts']);
    Route::get('/liked-posts', [PostController::class, 'getLikedPosts']);

    // Additional alternative routes
    Route::get('/my-saved-posts', [PostController::class, 'getSavedPosts']);
    Route::get('/my-liked-posts', [PostController::class, 'getLikedPosts']);

    // Efficient upload routes for large files
    Route::post('/posts/upload-url', [PostController::class, 'getUploadUrl']);
    Route::post('/posts/create-from-s3', [PostController::class, 'createFromS3']);
    Route::post('/posts/chunked-upload-url', [PostController::class, 'getChunkedUploadUrl']);
    Route::post('/posts/complete-chunked-upload', [PostController::class, 'completeChunkedUpload']);

    // Post search by tags
    Route::post('/posts/search/tags', [PostController::class, 'searchByTags']);

    // Individual post routes (must be after specific routes)
    Route::get('/posts/{post}', [PostController::class, 'show']);
    Route::delete('/posts/{post}', [PostController::class, 'destroy']);
    Route::post('/posts/{post}/like', [PostController::class, 'like']);
    Route::post('/posts/{post}/save', [PostController::class, 'save']);

    // Search routes
    Route::get('/search', [PostController::class, 'search']);

    // Business Account routes
    Route::get('/business-accounts', [BusinessAccountController::class, 'index']);
    Route::post('/business-accounts', [BusinessAccountController::class, 'store']);
    Route::get('/business-accounts/{businessAccount}', [BusinessAccountController::class, 'show']);
    Route::put('/business-accounts/{businessAccount}', [BusinessAccountController::class, 'update']);
    Route::delete('/business-accounts/{businessAccount}', [BusinessAccountController::class, 'destroy']);
    Route::post('/business-accounts/{businessAccount}/bookings', [BusinessAccountController::class, 'createBooking']);
    Route::get('/business-accounts/{businessAccount}/bookings', [BusinessAccountController::class, 'getBookings']);

    // Notification routes
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'getUnreadCount']);
    // Device management routes
    Route::prefix('devices')->group(function () {
        Route::post('/register', [App\Http\Controllers\Api\DeviceController::class, 'register']);
        Route::get('/', [App\Http\Controllers\Api\DeviceController::class, 'index']);
        Route::put('/{device}', [App\Http\Controllers\Api\DeviceController::class, 'update']);
        Route::put('/{device}/deactivate', [App\Http\Controllers\Api\DeviceController::class, 'deactivate']);
        Route::delete('/{device}', [App\Http\Controllers\Api\DeviceController::class, 'destroy']);
    });

    // Notification routes
    Route::prefix('notifications')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\NotificationController::class, 'index']);
        Route::get('/unread-count', [App\Http\Controllers\Api\NotificationController::class, 'unreadCount']);
        Route::get('/statistics', [App\Http\Controllers\Api\NotificationController::class, 'statistics']);
        Route::put('/{notification}/read', [App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
        Route::put('/{notification}/unread', [App\Http\Controllers\Api\NotificationController::class, 'markAsUnread']);
        Route::put('/mark-all-read', [App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);
        Route::put('/mark-multiple-read', [App\Http\Controllers\Api\NotificationController::class, 'markMultipleAsRead']);
        Route::delete('/{notification}', [App\Http\Controllers\Api\NotificationController::class, 'destroy']);
        Route::delete('/', [App\Http\Controllers\Api\NotificationController::class, 'deleteAll']);
        Route::post('/test', [App\Http\Controllers\Api\NotificationController::class, 'sendTest']);
    });

    // Admin routes
    Route::middleware('admin')->group(function () {
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{category}', [CategoryController::class, 'update']);
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
    });
});
