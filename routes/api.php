<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\ComparisonController;
use App\Http\Controllers\Api\V1\FavoriteController;
use App\Http\Controllers\Api\V1\MaintenanceRequestController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PropertyController;
use App\Http\Controllers\Api\V1\RecommendationController;
use App\Http\Controllers\Api\V1\RentalApplicationController;
use App\Http\Controllers\Api\V1\RentalContractController;
use App\Http\Controllers\Api\V1\ViewingRequestController;
use Illuminate\Support\Facades\Route;

/**
 * All routes here are prefixed /api/v1 automatically (see bootstrap/app.php).
 * Consumable by Flutter, React Native, native Android, and native iOS —
 * nothing in this file is web-session-specific.
 */
Route::prefix('v1')->group(function () {

    // ── Public ───────────────────────────────────────────────────
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);

    Route::get('properties', [PropertyController::class, 'index']);
    Route::get('properties/{slug}', [PropertyController::class, 'show']);

    // ── Authenticated (Sanctum token) ───────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/profile', [AuthController::class, 'profile']);
        Route::patch('auth/profile', [AuthController::class, 'updateProfile']);

        Route::get('favorites', [FavoriteController::class, 'index']);
        Route::post('favorites/{property}', [FavoriteController::class, 'store']);
        Route::delete('favorites/{property}', [FavoriteController::class, 'destroy']);

        Route::post('comparisons', [ComparisonController::class, 'compare']);
        Route::post('recommendations', [RecommendationController::class, 'store']);

        Route::get('applications', [RentalApplicationController::class, 'index']);
        Route::post('rental-spaces/{space}/applications', [RentalApplicationController::class, 'store']);
        Route::get('applications/{application}', [RentalApplicationController::class, 'show']);

        Route::get('viewings', [ViewingRequestController::class, 'index']);
        Route::post('properties/{property}/viewings', [ViewingRequestController::class, 'store']);

        Route::get('contracts', [RentalContractController::class, 'index']);
        Route::get('contracts/{contract}', [RentalContractController::class, 'show']);

        Route::get('payments', [PaymentController::class, 'index']);

        Route::get('maintenance', [MaintenanceRequestController::class, 'index']);
        Route::post('contracts/{contract}/maintenance', [MaintenanceRequestController::class, 'store']);

        Route::get('notifications', [NotificationController::class, 'index']);
        Route::patch('notifications/{id}/read', [NotificationController::class, 'markRead']);
        Route::patch('notifications/read-all', [NotificationController::class, 'markAllRead']);
    });
});
