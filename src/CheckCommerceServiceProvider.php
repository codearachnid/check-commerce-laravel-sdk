<?php

declare(strict_types=1);

namespace CheckCommerce\Laravel;

use Illuminate\Support\ServiceProvider;

class CheckCommerceServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/check-commerce.php', 'check-commerce');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/check-commerce.php' => config_path('check-commerce.php'),
        ], ['check-commerce-laravel-sdk', 'check-commerce-laravel-sdk-config']);
    }
}
