<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | API Credentials
    |--------------------------------------------------------------------------
    |
    | The API key and merchant number (MID) issued for your Check Commerce
    | account. Both are required; resolving the client without them throws
    | CheckCommerce\Exception\InvalidArgumentException naming the missing
    | configuration key.
    |
    */

    'api_key' => env('CHECK_COMMERCE_API_KEY'),

    'merchant_number' => env('CHECK_COMMERCE_MERCHANT_NUMBER'),

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    |
    | Either "production" or "sandbox". This selects the API base URL, so keep
    | it set to "sandbox" everywhere except live traffic.
    |
    */

    'environment' => env('CHECK_COMMERCE_ENVIRONMENT', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    |
    | Overrides the base URL implied by the environment. Leave this null unless
    | you are pointing at a proxy or a non-standard Check Commerce host.
    |
    */

    'base_url' => env('CHECK_COMMERCE_BASE_URL'),

    /*
    |--------------------------------------------------------------------------
    | Token Scopes
    |--------------------------------------------------------------------------
    |
    | Scopes requested when authenticating, as a list of strings or
    | CheckCommerce\Scope cases: "obp.tran", "obp.board", "obp.hp". An empty
    | list requests every scope available to the API key.
    |
    */

    'scopes' => [],

    /*
    |--------------------------------------------------------------------------
    | API Version
    |--------------------------------------------------------------------------
    |
    | The API version sent with every request.
    |
    */

    'api_version' => env('CHECK_COMMERCE_API_VERSION', '1.0'),

    /*
    |--------------------------------------------------------------------------
    | Timeouts
    |--------------------------------------------------------------------------
    |
    | Request and connection timeouts in seconds. These apply to the HTTP
    | client the SDK builds for itself; a client you bind yourself (see
    | "http_client" below) carries its own timeout settings.
    |
    */

    'timeout' => env('CHECK_COMMERCE_TIMEOUT', 30.0),

    'connect_timeout' => env('CHECK_COMMERCE_CONNECT_TIMEOUT', 10.0),

    /*
    |--------------------------------------------------------------------------
    | Retries
    |--------------------------------------------------------------------------
    |
    | Retryable failures back off exponentially with jitter, starting at
    | "retry_initial_delay_ms" and capped at "retry_max_delay_ms". Rate limits
    | are retried for every request; server errors and network failures are
    | retried for GET requests only, so a write is never blindly resent. Set
    | "max_retries" to 0 to disable retries entirely.
    |
    */

    'max_retries' => env('CHECK_COMMERCE_MAX_RETRIES', 2),

    'retry_initial_delay_ms' => env('CHECK_COMMERCE_RETRY_INITIAL_DELAY_MS', 500),

    'retry_max_delay_ms' => env('CHECK_COMMERCE_RETRY_MAX_DELAY_MS', 8000),

    /*
    |--------------------------------------------------------------------------
    | Token Expiry Margin
    |--------------------------------------------------------------------------
    |
    | Bearer tokens are refreshed this many seconds before the API reports them
    | as expired, so an in-flight request never races the expiry.
    |
    */

    'token_expiry_margin_seconds' => env('CHECK_COMMERCE_TOKEN_EXPIRY_MARGIN_SECONDS', 60),

    /*
    |--------------------------------------------------------------------------
    | Token Cache
    |--------------------------------------------------------------------------
    |
    | Bearer tokens are stored in Laravel's cache so every process and queue
    | worker shares one token instead of authenticating on its own. Set "store"
    | to a cache store name from config/cache.php, or leave it null to use the
    | default store. A shared store (redis, memcached, database) is strongly
    | recommended; the "array" and "file" stores work but do not share tokens
    | across servers.
    |
    | Cached entries expire with the token itself, and "prefix" is prepended to
    | the key the SDK derives from your credentials.
    |
    */

    'token_cache' => [

        'store' => env('CHECK_COMMERCE_TOKEN_CACHE_STORE'),

        'prefix' => env('CHECK_COMMERCE_TOKEN_CACHE_PREFIX', 'check-commerce'),

    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Client
    |--------------------------------------------------------------------------
    |
    | When "from_container" is enabled, the package hands the SDK whichever
    | PSR-18 client and PSR-17 request/stream factories are bound in the
    | container, letting you wrap transport with your own middleware. Disable
    | it to always let the SDK discover and build its own client.
    |
    */

    'http_client' => [

        'from_container' => env('CHECK_COMMERCE_HTTP_CLIENT_FROM_CONTAINER', true),

    ],

];
