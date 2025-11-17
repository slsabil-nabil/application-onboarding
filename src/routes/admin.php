<?php

use Illuminate\Support\Facades\Route;
use Slsabil\ApplicationOnboarding\Http\Controllers\SuperAdminApplicationsController;

Route::group([
    'prefix' => config('application_onboarding.admin_prefix', 'superadmin/applications'),
    'as' => 'superadmin.applications.',
    'middleware' => config('application_onboarding.admin_middleware', ['web', 'auth']),
], function () {
    Route::get('/', [SuperAdminApplicationsController::class, 'index'])
        ->name('index');

    Route::post('{application}/interpolation/start', [SuperAdminApplicationsController::class, 'updateInterpolationStatus'])
        ->name('interpolation.start');

    Route::get('{application}/interpolation', [SuperAdminApplicationsController::class, 'showInterpolationPage'])
        ->name('interpolation.show');

    Route::post('{application}/interpolation/documents', [SuperAdminApplicationsController::class, 'submitDocuments'])
        ->name('interpolation.submit-documents');

    Route::post('{application}/documents-request', [SuperAdminApplicationsController::class, 'sendDocumentsRequest'])
        ->name('documents-request');
});
