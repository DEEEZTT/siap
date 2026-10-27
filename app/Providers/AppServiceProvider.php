<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (!class_exists('finfo')) {
        $this->app->bind(
            \League\MimeTypeDetection\MimeTypeDetector::class,
            \League\MimeTypeDetection\ExtensionMimeTypeDetector::class
        );
    }
    }
}
