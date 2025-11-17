<?php

namespace Slsabil\ApplicationOnboarding;

use Illuminate\Support\ServiceProvider;

class ApplicationOnboardingServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/application_onboarding.php',
            'application_onboarding'
        );
    }

    public function boot()
    {
        // مسارات الواجهة العامة
        $this->loadRoutesFrom(__DIR__.'/routes/public.php');

        // مسارات السوبر أدمن
        $this->loadRoutesFrom(__DIR__.'/routes/admin.php');

        // الواجهات
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'application-onboarding');

        // الميجريشن
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // نشر ملف الكونفيج
        $this->publishes([
            __DIR__.'/../config/application_onboarding.php' => config_path('application_onboarding.php'),
        ], 'application-onboarding-config');

        // نشر الواجهات
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/application-onboarding'),
        ], 'application-onboarding-views');

        // نشر الميجريشن (اختياري)
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'application-onboarding-migrations');
    }
}

