<?php

declare(strict_types=1);

namespace CheckCommerce\Laravel;

use CheckCommerce\Auth\TokenStoreInterface;
use CheckCommerce\CheckCommerceClient;
use CheckCommerce\Configuration;
use CheckCommerce\Exception\InvalidArgumentException;
use CheckCommerce\Laravel\Auth\CacheTokenStore;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class CheckCommerceServiceProvider extends ServiceProvider
{
    /**
     * Config keys handed to the core SDK's Configuration, in its own spelling.
     *
     * @var list<string>
     */
    protected const array CONFIGURATION_KEYS = [
        'api_key',
        'merchant_number',
        'environment',
        'base_url',
        'scopes',
        'api_version',
        'timeout',
        'connect_timeout',
        'max_retries',
        'retry_initial_delay_ms',
        'retry_max_delay_ms',
        'token_expiry_margin_seconds',
    ];

    /**
     * Credentials with no usable default, and the variable that supplies them.
     *
     * @var array<string, string>
     */
    protected const array REQUIRED_CREDENTIALS = [
        'api_key' => 'CHECK_COMMERCE_API_KEY',
        'merchant_number' => 'CHECK_COMMERCE_MERCHANT_NUMBER',
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/check-commerce.php', 'check-commerce');

        $this->app->singleton(TokenStoreInterface::class, fn (): TokenStoreInterface => new CacheTokenStore(
            $this->app->make(CacheFactory::class),
            $this->tokenCacheStore(),
            $this->tokenCachePrefix(),
        ));

        $this->app->singleton(CheckCommerceClient::class, fn (): CheckCommerceClient => new CheckCommerceClient(
            $this->configuration(),
            httpClient: $this->transportDependency(ClientInterface::class),
            requestFactory: $this->transportDependency(RequestFactoryInterface::class),
            streamFactory: $this->transportDependency(StreamFactoryInterface::class),
            tokenStore: $this->app->make(TokenStoreInterface::class),
        ));

        $this->app->alias(CheckCommerceClient::class, 'check-commerce');
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

    /**
     * Builds the SDK configuration from the package config.
     *
     * Credentials are checked here rather than at boot so a misconfigured
     * application fails when it first talks to Check Commerce, not on every
     * artisan command it runs.
     */
    protected function configuration(): Configuration
    {
        $config = $this->config('check-commerce');
        $config = is_array($config) ? $config : [];

        foreach (self::REQUIRED_CREDENTIALS as $key => $variable) {
            $value = $config[$key] ?? null;

            if (! is_scalar($value) || trim((string) $value) === '') {
                throw new InvalidArgumentException(sprintf(
                    'The Check Commerce %s is not configured. Set %s in your environment, or "%s" in config/check-commerce.php.',
                    str_replace('_', ' ', $key),
                    $variable,
                    $key,
                ));
            }
        }

        return Configuration::fromArray(Arr::only($config, self::CONFIGURATION_KEYS));
    }

    /**
     * Resolves a PSR-18 client or PSR-17 factory the application has bound.
     *
     * Returning null leaves the core SDK to discover its own implementation.
     *
     * @template TDependency of object
     *
     * @param  class-string<TDependency>  $abstract
     * @return TDependency|null
     */
    protected function transportDependency(string $abstract): ?object
    {
        if ($this->config('check-commerce.http_client.from_container', true) !== true) {
            return null;
        }

        return $this->app->bound($abstract) ? $this->app->make($abstract) : null;
    }

    protected function tokenCacheStore(): ?string
    {
        $store = $this->config('check-commerce.token_cache.store');

        return is_string($store) && $store !== '' ? $store : null;
    }

    protected function tokenCachePrefix(): string
    {
        $prefix = $this->config('check-commerce.token_cache.prefix', 'check-commerce');

        return is_scalar($prefix) ? (string) $prefix : 'check-commerce';
    }

    protected function config(string $key, mixed $default = null): mixed
    {
        return $this->app->make(ConfigRepository::class)->get($key, $default);
    }
}
