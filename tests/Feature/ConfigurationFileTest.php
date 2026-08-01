<?php

declare(strict_types=1);

it('merges the package config', function () {
    expect(config('check-commerce.environment'))->toBe('production')
        ->and(config('check-commerce.api_version'))->toBe('1.0')
        ->and(config('check-commerce.scopes'))->toBe([])
        ->and(config('check-commerce.timeout'))->toBe(30.0)
        ->and(config('check-commerce.connect_timeout'))->toBe(10.0)
        ->and(config('check-commerce.max_retries'))->toBe(2)
        ->and(config('check-commerce.retry_initial_delay_ms'))->toBe(500)
        ->and(config('check-commerce.retry_max_delay_ms'))->toBe(8000)
        ->and(config('check-commerce.token_expiry_margin_seconds'))->toBe(60)
        ->and(config('check-commerce.token_cache.prefix'))->toBe('check-commerce')
        ->and(config('check-commerce.token_cache.store'))->toBeNull()
        ->and(config('check-commerce.http_client.from_container'))->toBeTrue();
});

it('lets the application override the merged config', function () {
    config()->set('check-commerce.environment', 'sandbox');

    expect(config('check-commerce.environment'))->toBe('sandbox');
});

it('publishes the config file under the package tags', function () {
    $this->artisan('vendor:publish', ['--tag' => 'check-commerce-laravel-sdk-config'])->assertSuccessful();

    expect(config_path('check-commerce.php'))->toBeFile();
})->after(fn () => @unlink(config_path('check-commerce.php')));
