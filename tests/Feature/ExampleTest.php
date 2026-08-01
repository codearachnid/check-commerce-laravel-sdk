<?php

declare(strict_types=1);

use CheckCommerceLaravelSDK\CheckCommerceLaravelSDK\CheckCommerceLaravelSDK;

it('resolves the singleton', function () {
    expect(app(CheckCommerceLaravelSDK::class))->toBeInstanceOf(CheckCommerceLaravelSDK::class);
});

it('returns the same instance from the container', function () {
    expect(app(CheckCommerceLaravelSDK::class))->toBe(app(CheckCommerceLaravelSDK::class));
});

it('merges the package config', function () {
    expect(config('check-commerce-laravel-sdk.placeholder'))->toBe('default');
});

it('loads the package translations', function () {
    expect(trans('check-commerce-laravel-sdk::messages.placeholder'))->toBe('CheckCommerceLaravelSDK placeholder translation.');
});

it('loads the package views', function () {
    expect(view()->exists('check-commerce-laravel-sdk::placeholder'))->toBeTrue();
});

it('registers the artisan command', function () {
    $this->artisan('check-commerce-laravel-sdk:placeholder')
        ->expectsOutputToContain('CheckCommerceLaravelSDK placeholder command executed.')
        ->assertSuccessful();
});
