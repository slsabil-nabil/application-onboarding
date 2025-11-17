<?php

use Illuminate\Support\Facades\Route;
use Slsabil\ApplicationOnboarding\Http\Controllers\PublicApplicationController;

Route::group([
    'prefix' => config('application_onboarding.public_prefix', 'apply'),
    'as' => 'application.',
    'middleware' => ['web'],
], function () {
    Route::get('{token?}', [PublicApplicationController::class, 'create'])
        ->name('create');

    Route::post('{token?}', [PublicApplicationController::class, 'submit'])
        ->name('store');

    Route::get('success/page/done', function () {
        return view('application-onboarding::public.success');
    })->name('success');
});

Route::group([
    'prefix' => config('application_onboarding.interpolation_prefix', 'application/interpolation'),
    'as' => 'interpolation.',
    'middleware' => ['web'],
], function () {
    Route::get('{token}', [PublicApplicationController::class, 'interpolationForm'])
        ->name('show');

    Route::post('{token}', [PublicApplicationController::class, 'interpolationSubmit'])
        ->name('submit');

    Route::get('success/page/done', function () {
        return view('application-onboarding::public.interpolation-success');
    })->name('success');
});
