<?php

declare(strict_types=1);

namespace CheckCommerceLaravelSDK\CheckCommerceLaravelSDK;

use CheckCommerceLaravelSDK\CheckCommerceLaravelSDK\Console\Commands\CheckCommerceLaravelSDKCommand;
use Illuminate\Support\ServiceProvider;

class CheckCommerceLaravelSDKServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/check-commerce-laravel-sdk.php', 'check-commerce-laravel-sdk');

        $this->app->singleton(CheckCommerceLaravelSDK::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/check-commerce-laravel-sdk.php');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'check-commerce-laravel-sdk');

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'check-commerce-laravel-sdk');

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/check-commerce-laravel-sdk.php' => config_path('check-commerce-laravel-sdk.php'),
        ], ['check-commerce-laravel-sdk', 'check-commerce-laravel-sdk-config']);

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/check-commerce-laravel-sdk'),
        ], ['check-commerce-laravel-sdk', 'check-commerce-laravel-sdk-views']);

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/check-commerce-laravel-sdk'),
        ], ['check-commerce-laravel-sdk', 'check-commerce-laravel-sdk-lang']);

        $this->publishes([
            __DIR__.'/../public' => public_path('vendor/check-commerce-laravel-sdk'),
        ], ['check-commerce-laravel-sdk', 'check-commerce-laravel-sdk-assets']);

        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], ['check-commerce-laravel-sdk', 'check-commerce-laravel-sdk-migrations']);

        $this->commands([
            CheckCommerceLaravelSDKCommand::class,
        ]);
    }
}
