<?php

use Illuminate\Support\Facades\Route;
use Slsabil\ApplicationOnboarding\Http\Controllers\SuperAdminApplicationsController;
use Slsabil\ApplicationOnboarding\Http\Controllers\FormBuilderController;

// ===== طلبات الإعداد (طلبات الانضمام) =====
Route::group([
    'prefix' => config('application_onboarding.admin_prefix', 'superadmin/applications'),
    'as' => 'superadmin.applications.',
    'middleware' => config('application_onboarding.admin_middleware', ['web', 'auth']),
], function () {
    // قائمة الطلبات
    Route::get('/', [SuperAdminApplicationsController::class, 'index'])
        ->name('index');

    // عرض تفاصيل الطلب (زر View)
    Route::get('{application}', [SuperAdminApplicationsController::class, 'show'])
        ->name('show');

    // بدء الاستيفاء (تغيير حالة interpolation)
    Route::post('{application}/interpolation/start', [SuperAdminApplicationsController::class, 'updateInterpolationStatus'])
        ->name('interpolation.start');

    // صفحة إعداد الاستيفاء (Application Review)
    Route::get('{application}/interpolation', [SuperAdminApplicationsController::class, 'showInterpolationPage'])
        ->name('interpolation.show');

    // إرسال طلب الوثائق/التصحيحات
    Route::post('{application}/interpolation/documents', [SuperAdminApplicationsController::class, 'submitDocuments'])
        ->name('interpolation.submit-documents');

    Route::post('{application}/documents-request', [SuperAdminApplicationsController::class, 'sendDocumentsRequest'])
        ->name('documents-request');

    // الموافقة على الطلب
    Route::post('{application}/approve', [SuperAdminApplicationsController::class, 'approve'])
        ->name('approve');

    // رفض الطلب
    Route::post('{application}/decline', [SuperAdminApplicationsController::class, 'decline'])
        ->name('decline');
});


// ===== مُنشئ فورم الطلب (Form Builder) =====
Route::group([
    'prefix' => config('application_onboarding.form_builder_prefix', 'superadmin/form-builder'),
    'as' => 'superadmin.form-builder.',
    'middleware' => config('application_onboarding.admin_middleware', ['web', 'auth']),
], function () {
    Route::get('/', [FormBuilderController::class, 'index'])->name('index');
    Route::post('/', [FormBuilderController::class, 'store'])->name('store');

    Route::get('{field}/edit', [FormBuilderController::class, 'edit'])->name('edit');
    Route::put('{field}', [FormBuilderController::class, 'update'])->name('update');

    Route::delete('{field}', [FormBuilderController::class, 'destroy'])->name('destroy');

    Route::post('reorder', [FormBuilderController::class, 'reorder'])->name('reorder');
});
