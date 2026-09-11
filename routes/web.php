<?php

use App\Http\Controllers\Admin\DssController;
use App\Http\Controllers\Admin\OwnerVerificationController as AdminOwnerVerificationController;
use App\Http\Controllers\Admin\PropertyController as AdminPropertyController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\ApplicationDocumentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Owner\MaintenanceRequestController as OwnerMaintenanceRequestController;
use App\Http\Controllers\Owner\OwnerVerificationController;
use App\Http\Controllers\Owner\PaymentController as OwnerPaymentController;
use App\Http\Controllers\Owner\PropertyController as OwnerPropertyController;
use App\Http\Controllers\Owner\PropertyImageController;
use App\Http\Controllers\Owner\RentalApplicationController as OwnerRentalApplicationController;
use App\Http\Controllers\Owner\RentalContractController as OwnerRentalContractController;
use App\Http\Controllers\Owner\RentalSpaceController;
use App\Http\Controllers\Owner\RentalSpaceImageController;
use App\Http\Controllers\Owner\SceneHotspotController;
use App\Http\Controllers\Owner\ViewingRequestController as OwnerViewingRequestController;
use App\Http\Controllers\Owner\VirtualTourController;
use App\Http\Controllers\Owner\VirtualTourSceneController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\PropertyController as PublicPropertyController;
use App\Http\Controllers\Tenant\ComparisonController;
use App\Http\Controllers\Tenant\FavoriteController;
use App\Http\Controllers\Tenant\MaintenanceRequestController as TenantMaintenanceRequestController;
use App\Http\Controllers\Tenant\PaymentController as TenantPaymentController;
use App\Http\Controllers\Tenant\RecommendationController;
use App\Http\Controllers\Tenant\RentalApplicationController as TenantRentalApplicationController;
use App\Http\Controllers\Tenant\RentalContractController as TenantRentalContractController;
use App\Http\Controllers\Tenant\ReviewController as TenantReviewController;
use App\Http\Controllers\Tenant\ViewingRequestController as TenantViewingRequestController;
use App\Http\Controllers\VerificationDocumentController;
use Illuminate\Support\Facades\Route;

// ── Public ───────────────────────────────────────────────────────

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/properties', [PublicPropertyController::class, 'index'])->name('properties.index');
Route::get('/properties/{slug}', [PublicPropertyController::class, 'show'])->name('properties.show');
Route::get('/properties/{property}/virtual-tour/{tour}/scenes/{scene}/panorama', [PublicPropertyController::class, 'panorama'])
    ->name('virtual-tour.panorama');

// ── Authenticated ────────────────────────────────────────────────

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.readAll');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Owner / Manager property management
    Route::middleware(['role:owner,manager,super_admin', 'owner.verified'])->prefix('owner')->name('owner.')->group(function () {
        Route::resource('properties', OwnerPropertyController::class)->except(['show']);

        Route::delete('properties/{property}/images/{image}', [PropertyImageController::class, 'destroy'])
            ->name('properties.images.destroy');
        Route::patch('properties/{property}/images/{image}/cover', [PropertyImageController::class, 'makeCover'])
            ->name('properties.images.cover');

        Route::get('properties/{property}/spaces', [RentalSpaceController::class, 'index'])->name('properties.spaces.index');
        Route::get('properties/{property}/spaces/create', [RentalSpaceController::class, 'create'])->name('properties.spaces.create');
        Route::post('properties/{property}/spaces', [RentalSpaceController::class, 'store'])->name('properties.spaces.store');
        Route::get('properties/{property}/spaces/{space}/edit', [RentalSpaceController::class, 'edit'])->name('properties.spaces.edit');
        Route::put('properties/{property}/spaces/{space}', [RentalSpaceController::class, 'update'])->name('properties.spaces.update');
        Route::delete('properties/{property}/spaces/{space}', [RentalSpaceController::class, 'destroy'])->name('properties.spaces.destroy');
        Route::delete('properties/{property}/spaces/{space}/images/{image}', [RentalSpaceImageController::class, 'destroy'])->name('properties.spaces.images.destroy');
        Route::patch('properties/{property}/spaces/{space}/images/{image}/cover', [RentalSpaceImageController::class, 'makeCover'])->name('properties.spaces.images.cover');

        // Virtual tour management
        Route::get('properties/{property}/tour', [VirtualTourController::class, 'show'])->name('properties.tour.show');
        Route::post('properties/{property}/tour', [VirtualTourController::class, 'store'])->name('properties.tour.store');
        Route::patch('properties/{property}/tour/publish', [VirtualTourController::class, 'publish'])->name('properties.tour.publish');

        Route::post('properties/{property}/tour/{tour}/scenes', [VirtualTourSceneController::class, 'store'])->name('properties.tour.scenes.store');
        Route::put('properties/{property}/tour/{tour}/scenes/{scene}', [VirtualTourSceneController::class, 'update'])->name('properties.tour.scenes.update');
        Route::delete('properties/{property}/tour/{tour}/scenes/{scene}', [VirtualTourSceneController::class, 'destroy'])->name('properties.tour.scenes.destroy');

        Route::post('properties/{property}/tour/{tour}/scenes/{scene}/hotspots', [SceneHotspotController::class, 'store'])->name('properties.tour.hotspots.store');
        Route::delete('properties/{property}/tour/{tour}/scenes/{scene}/hotspots/{hotspot}', [SceneHotspotController::class, 'destroy'])->name('properties.tour.hotspots.destroy');

        // Applications & viewing requests for properties this user owns/manages
        Route::get('applications', [OwnerRentalApplicationController::class, 'index'])->name('applications.index');
        Route::get('applications/{application}', [OwnerRentalApplicationController::class, 'show'])->name('applications.show');
        Route::patch('applications/{application}/review', [OwnerRentalApplicationController::class, 'review'])->name('applications.review');

        Route::get('viewings', [OwnerViewingRequestController::class, 'index'])->name('viewings.index');
        Route::patch('viewings/{viewing}', [OwnerViewingRequestController::class, 'update'])->name('viewings.update');

        // Rental contracts (created from an approved application)
        Route::get('applications/{application}/contract/create', [OwnerRentalContractController::class, 'create'])->name('applications.contract.create');
        Route::post('applications/{application}/contract', [OwnerRentalContractController::class, 'store'])->name('applications.contract.store');
        Route::get('contracts', [OwnerRentalContractController::class, 'index'])->name('contracts.index');
        Route::get('contracts/{contract}', [OwnerRentalContractController::class, 'show'])->name('contracts.show');
        Route::patch('contracts/{contract}/activate', [OwnerRentalContractController::class, 'activate'])->name('contracts.activate');
        Route::patch('contracts/{contract}/terminate', [OwnerRentalContractController::class, 'terminate'])->name('contracts.terminate');
        Route::get('contracts/{contract}/pdf', [OwnerRentalContractController::class, 'downloadPdf'])->name('contracts.pdf');

        // Payments (recorded against a contract)
        Route::post('contracts/{contract}/payments', [OwnerPaymentController::class, 'store'])->name('contracts.payments.store');

        // Maintenance requests for owned/managed properties
        Route::get('maintenance', [OwnerMaintenanceRequestController::class, 'index'])->name('maintenance.index');
        Route::patch('maintenance/{maintenanceRequest}', [OwnerMaintenanceRequestController::class, 'updateStatus'])->name('maintenance.update');
    });

    // Tenant actions
    Route::middleware('role:tenant')->prefix('tenant')->name('tenant.')->group(function () {
        Route::get('applications', [TenantRentalApplicationController::class, 'index'])->name('applications.index');
        Route::get('applications/{application}', [TenantRentalApplicationController::class, 'show'])->name('applications.show');
        Route::get('rental-spaces/{space}/apply', [TenantRentalApplicationController::class, 'create'])->name('applications.create');
        Route::post('rental-spaces/{space}/apply', [TenantRentalApplicationController::class, 'store'])->name('applications.store');

        Route::get('viewings', [TenantViewingRequestController::class, 'index'])->name('viewings.index');
        Route::get('properties/{property}/request-viewing', [TenantViewingRequestController::class, 'create'])->name('viewings.create');
        Route::post('properties/{property}/request-viewing', [TenantViewingRequestController::class, 'store'])->name('viewings.store');

        Route::get('contracts', [TenantRentalContractController::class, 'index'])->name('contracts.index');
        Route::get('contracts/{contract}', [TenantRentalContractController::class, 'show'])->name('contracts.show');
        Route::get('contracts/{contract}/pdf', [TenantRentalContractController::class, 'downloadPdf'])->name('contracts.pdf');
        Route::get('contracts/{contract}/review', [TenantReviewController::class, 'create'])->name('contracts.review.create');
        Route::post('contracts/{contract}/review', [TenantReviewController::class, 'store'])->name('contracts.review.store');

        Route::get('payments', [TenantPaymentController::class, 'index'])->name('payments.index');

        Route::get('maintenance', [TenantMaintenanceRequestController::class, 'index'])->name('maintenance.index');
        Route::get('contracts/{contract}/maintenance/create', [TenantMaintenanceRequestController::class, 'create'])->name('maintenance.create');
        Route::post('contracts/{contract}/maintenance', [TenantMaintenanceRequestController::class, 'store'])->name('maintenance.store');

        Route::get('recommendations', [RecommendationController::class, 'create'])->name('recommendations.create');
        Route::post('recommendations', [RecommendationController::class, 'store'])->name('recommendations.store');

        Route::get('favorites', [FavoriteController::class, 'index'])->name('favorites.index');
        Route::post('properties/{property}/favorite', [FavoriteController::class, 'toggle'])->name('favorites.toggle');

        Route::get('compare', [ComparisonController::class, 'show'])->name('compare.show');
    });

    // Private document download — authorized per-request, never a public URL
    Route::get('documents/{document}/download', [ApplicationDocumentController::class, 'download'])->name('documents.download');
    Route::get('documents/verification/{document}/download', [VerificationDocumentController::class, 'download'])->name('verification-documents.download');

    // Owner verification — deliberately OUTSIDE the 'owner.verified' gate above,
    // since an unverified owner must still be able to reach this page.
    Route::middleware('role:owner')->prefix('owner')->name('owner.')->group(function () {
        Route::get('verification', [OwnerVerificationController::class, 'show'])->name('verification.show');
        Route::post('verification', [OwnerVerificationController::class, 'store'])->name('verification.store');
    });

    // Super Admin
    Route::middleware('role:super_admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('properties', [AdminPropertyController::class, 'index'])->name('properties.index');
        Route::patch('properties/{property}/verify', [AdminPropertyController::class, 'verify'])->name('properties.verify');

        Route::get('dss', [DssController::class, 'index'])->name('dss.index');
        Route::patch('dss', [DssController::class, 'update'])->name('dss.update');

        Route::get('owner-verifications', [AdminOwnerVerificationController::class, 'index'])->name('owner-verifications.index');
        Route::get('owner-verifications/{ownerVerification}', [AdminOwnerVerificationController::class, 'show'])->name('owner-verifications.show');
        Route::patch('owner-verifications/{ownerVerification}/approve', [AdminOwnerVerificationController::class, 'approve'])->name('owner-verifications.approve');
        Route::patch('owner-verifications/{ownerVerification}/reject', [AdminOwnerVerificationController::class, 'reject'])->name('owner-verifications.reject');
        Route::patch('owner-verifications/{ownerVerification}/request-more', [AdminOwnerVerificationController::class, 'requestMoreDocuments'])->name('owner-verifications.request-more');

        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/{type}', [ReportController::class, 'show'])->name('reports.show');
        Route::get('reports/{type}/export/csv', [ReportController::class, 'exportCsv'])->name('reports.export.csv');
        Route::get('reports/{type}/export/pdf', [ReportController::class, 'exportPdf'])->name('reports.export.pdf');

        Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('users/{user}/password/edit', [AdminUserController::class, 'editPassword'])->name('users.password.edit');
        Route::patch('users/{user}/password', [AdminUserController::class, 'updatePassword'])->name('users.password.update');
    });
});

require __DIR__.'/auth.php';
