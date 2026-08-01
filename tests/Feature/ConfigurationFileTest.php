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

it('reads the CHECK_COMMERCE_* environment variables', function () {
    putenv('CHECK_COMMERCE_API_KEY=env-api-key');
    putenv('CHECK_COMMERCE_MERCHANT_NUMBER=123456');
    putenv('CHECK_COMMERCE_ENVIRONMENT=sandbox');
    putenv('CHECK_COMMERCE_BASE_URL=https://proxy.example.com/api');
    putenv('CHECK_COMMERCE_TOKEN_CACHE_STORE=redis');

    $config = require __DIR__.'/../../config/check-commerce.php';

    expect($config['api_key'])->toBe('env-api-key')
        ->and($config['merchant_number'])->toBe('123456')
        ->and($config['environment'])->toBe('sandbox')
        ->and($config['base_url'])->toBe('https://proxy.example.com/api')
        ->and($config['token_cache']['store'])->toBe('redis');
})->after(function () {
    foreach ([
        'CHECK_COMMERCE_API_KEY',
        'CHECK_COMMERCE_MERCHANT_NUMBER',
        'CHECK_COMMERCE_ENVIRONMENT',
        'CHECK_COMMERCE_BASE_URL',
        'CHECK_COMMERCE_TOKEN_CACHE_STORE',
    ] as $variable) {
        putenv($variable);
    }
});

it('falls back to its defaults when nothing is in the environment', function () {
    $config = require __DIR__.'/../../config/check-commerce.php';

    expect($config['api_key'])->toBeNull()
        ->and($config['merchant_number'])->toBeNull()
        ->and($config['environment'])->toBe('production')
        ->and($config['base_url'])->toBeNull()
        ->and($config['token_cache']['store'])->toBeNull();
});

it('lets the application override the merged config', function () {
    config()->set('check-commerce.environment', 'sandbox');

    expect(config('check-commerce.environment'))->toBe('sandbox');
});

it('publishes the config file under the package tags', function () {
    $this->artisan('vendor:publish', ['--tag' => 'check-commerce-laravel-sdk-config'])->assertSuccessful();

    expect(config_path('check-commerce.php'))->toBeFile();
})->after(fn () => @unlink(config_path('check-commerce.php')));
