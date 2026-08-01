<div align="center">
    <h1>Check Commerce for Laravel</h1>
</div>

<p align="center">
    <a href="https://packagist.org/packages/codearachnid/check-commerce-laravel-sdk"><img src="https://img.shields.io/packagist/v/codearachnid/check-commerce-laravel-sdk.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/codearachnid/check-commerce-laravel-sdk"><img src="https://img.shields.io/packagist/php-v/codearachnid/check-commerce-laravel-sdk.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://packagist.org/packages/codearachnid/check-commerce-laravel-sdk"><img src="https://badge.laravel.cloud/badge/codearachnid/check-commerce-laravel-sdk?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/codearachnid/check-commerce-laravel-sdk/actions"><img alt="GitHub Workflow Status (main)" src="https://img.shields.io/github/actions/workflow/status/codearachnid/check-commerce-laravel-sdk/tests.yml?branch=main&label=Tests&style=flat-square"></a>
    <a href="https://packagist.org/packages/codearachnid/check-commerce-laravel-sdk"><img src="https://img.shields.io/packagist/dt/codearachnid/check-commerce-laravel-sdk.svg?style=flat-square" alt="Total Downloads"></a>
</p>

Laravel integration for the [Check Commerce (OBP Link) API](https://sandbox.checkcommerce.com/api/docs/index.html) — ACH, RTP, paper check and IAT payments, stored consumers, recurring subscriptions, hosted payment pages, batch processing and merchant boarding.

Everything that talks to the API lives in [`codearachnid/check-commerce-php-sdk`](https://github.com/codearachnid/check-commerce-php-sdk). This package is the Laravel half of it:

- **Configured like the rest of your app** — a publishable config file mapping `CHECK_COMMERCE_*` environment variables onto every SDK option.
- **Resolved from the container** — `CheckCommerceClient` is a singleton you can inject, plus a `CheckCommerce` facade.
- **Tokens shared across processes** — bearer tokens live in Laravel's cache and expire with the token itself, so workers stop re-authenticating one at a time.
- **Transport you can replace** — bind your own PSR-18 client and PSR-17 factories and the SDK uses them.
- **Testable without HTTP mocks** — `CheckCommerce::fake()` queues responses and records requests in feature tests.

## Requirements

- PHP 8.3+
- Laravel 12 or 13
- A [PSR-18](https://www.php-fig.org/psr/psr-18/) HTTP client and [PSR-17](https://www.php-fig.org/psr/psr-17/) factories ([Guzzle](https://github.com/guzzle/guzzle) is recommended and auto-discovered)

## Installation

```bash
composer require codearachnid/check-commerce-laravel-sdk guzzlehttp/guzzle
```

The core SDK is pulled in as a dependency; you never install it separately. Omit `guzzlehttp/guzzle` if your application already ships a PSR-18 client — the SDK discovers whatever implementation is installed.

Publish the config file:

```bash
php artisan vendor:publish --tag="check-commerce-laravel-sdk-config"
```

Then add your credentials:

```dotenv
CHECK_COMMERCE_API_KEY=your-api-key
CHECK_COMMERCE_MERCHANT_NUMBER=999997
CHECK_COMMERCE_ENVIRONMENT=sandbox

# Optional
CHECK_COMMERCE_BASE_URL=
CHECK_COMMERCE_API_VERSION=1.0
CHECK_COMMERCE_TIMEOUT=30
CHECK_COMMERCE_CONNECT_TIMEOUT=10
CHECK_COMMERCE_MAX_RETRIES=2
CHECK_COMMERCE_TOKEN_EXPIRY_MARGIN_SECONDS=60
CHECK_COMMERCE_TOKEN_CACHE_STORE=redis
CHECK_COMMERCE_TOKEN_CACHE_PREFIX=check-commerce
```

Every key in `config/check-commerce.php` is documented in place, including scopes, retry backoff and the HTTP client toggle.

## Usage

Inject the client anywhere the container builds your class:

```php
use CheckCommerce\CheckCommerceClient;

class CheckoutController
{
    public function __construct(private CheckCommerceClient $checkCommerce) {}

    public function store(Request $request)
    {
        $result = $this->checkCommerce->transactions->debit([...]);
    }
}
```

Or reach for the facade:

```php
use CheckCommerce\Laravel\Facades\CheckCommerce;

CheckCommerce::transactions()->debit([...]);
```

The facade exposes the SDK's services as methods — `transactions()`, `consumers()`, `subscriptions()`, `batches()`, `hostedPages()`, `boarding()` — plus `authenticate()`. Injected clients read them as properties (`$client->transactions`); both return the same objects.

Credentials are validated the first time the client is resolved, not during boot, so an application missing `CHECK_COMMERCE_API_KEY` still runs its migrations and queue workers.

### Transactions

```php
use CheckCommerce\Enums\PaymentType;
use CheckCommerce\Laravel\Facades\CheckCommerce;

$result = CheckCommerce::transactions()->debit([
    'merchantNumber' => config('check-commerce.merchant_number'),
    'amount' => 42.50,
    'referenceNumber' => 'INV-1001',
    'consumerInfo' => [
        'name' => 'Jane Doe',
        'bankAccountNumber' => '1234567890',
        'bankRoutingNumber' => 121000248,
    ],
]);

$result->transactionId;     // 123456789
$result->status?->value;    // "Processed"

// Credits, voids and refunds work the same way:
$merchantNumber = config('check-commerce.merchant_number');

CheckCommerce::transactions()->credit([...]);
CheckCommerce::transactions()->void(['merchantNumber' => $merchantNumber, 'originalTransaction' => ['transactionId' => 123456789]]);
CheckCommerce::transactions()->refund(['merchantNumber' => $merchantNumber, 'originalTransaction' => ['referenceNumber' => 'INV-1001']]);

// Any rail, any transaction type:
CheckCommerce::transactions()->create([...], PaymentType::Rtp);

// Status lookups:
$status = CheckCommerce::transactions()->status(transactionId: 123456789);

if ($status->isDeclined()) {
    report(new RuntimeException($status->processingFailure?->detail ?? 'Declined'));
}
```

### Consumers

```php
$created = CheckCommerce::consumers()->create([
    'name' => 'Jane Doe',
    'email' => 'jane@example.com',
    'bankAccountNumber' => '1234567890',
    'bankRoutingNumber' => 121000248,
]);

$consumer = CheckCommerce::consumers()->retrieve($created->consumerId);
CheckCommerce::consumers()->update($created->consumerId, ['phoneNumber' => '5125551234']);

// Charge a stored consumer:
CheckCommerce::transactions()->debit([
    'merchantNumber' => config('check-commerce.merchant_number'),
    'amount' => 42.50,
    'consumerInfo' => ['consumerId' => $created->consumerId],
]);

// List endpoints page lazily:
foreach (CheckCommerce::consumers()->list(['city' => 'Austin'])->autoPagingIterator() as $consumer) {
    // ...
}
```

### Subscriptions

```php
use CheckCommerce\Enums\SubscriptionEndCode;
use CheckCommerce\Enums\SubscriptionStatus;
use CheckCommerce\Enums\TransactionType;

$created = CheckCommerce::subscriptions()->create([
    'startTime' => now()->addMonth()->startOfMonth()->toDateTimeImmutable(),
    'amount' => 25.00,
    'schCode' => 'Monthly:1',
    'endCode' => SubscriptionEndCode::Indefinite,
    'transactionType' => TransactionType::Debit,
    'status' => SubscriptionStatus::Active,
    'consumerInfo' => ['consumerId' => $consumerId],
]);

CheckCommerce::subscriptions()->update($created->subscriptionId, ['amount' => 30.00]);
```

### Hosted payment pages

```php
$link = CheckCommerce::hostedPages()->createLink([
    'customer' => ['name' => 'Jane Doe', 'email' => 'jane@example.com'],
    'order' => [
        'subTotal' => 89.95,
        'tax' => 10.00,
        'total' => 99.95,
        'returnURL' => route('checkout.thanks'),
    ],
    'orderItems' => [
        ['name' => 'Widget', 'quantity' => 1, 'price' => 89.95],
    ],
]);

return redirect()->away($link->url);
```

### Batches

```php
use CheckCommerce\Enums\FileDelimiter;

$merchantNumber = config('check-commerce.merchant_number');

$batch = CheckCommerce::batches()->submit([
    ['merchantNumber' => $merchantNumber, 'transactionType' => 'Debit', 'amount' => 42.50, 'consumerInfo' => [...]],
    ['merchantNumber' => $merchantNumber, 'transactionType' => 'Debit', 'amount' => 19.99, 'consumerInfo' => [...]],
]);

// Or upload a file:
$batch = CheckCommerce::batches()->uploadFile(storage_path('app/batches/today.csv'), FileDelimiter::Comma);

CheckCommerce::batches()->status($batch->batchId)->status?->value;   // "Pending" | "Processing" | "Processed" | "Declined"
```

### Merchant boarding

```php
$result = CheckCommerce::boarding()->board(['merchants' => [/* boarding records */]]);

foreach ($result->boardingFailures as $failure) {
    Log::warning('Boarding failed', [
        'company' => $failure->companyName,
        'detail' => $failure->processingFailure?->detail,
    ]);
}
```

### Errors

Every API failure is a typed exception from the core SDK — `ValidationException`, `AuthenticationException`, `AuthorizationException`, `NotFoundException`, `RateLimitException`, `ServerException`, `ApiException` and `TransportException`, all implementing `CheckCommerce\Exception\CheckCommerceException`:

```php
use CheckCommerce\Exception\ApiException;
use CheckCommerce\Exception\ValidationException;

try {
    CheckCommerce::transactions()->debit([...]);
} catch (ValidationException $e) {
    return back()->withErrors(collect($e->getValidationErrors())
        ->mapWithKeys(fn ($error) => [$error->property => $error->detail])
        ->all());
} catch (ApiException $e) {
    Log::error($e->getMessage(), [
        'status' => $e->getStatusCode(),
        'code' => $e->getErrorCode(),
        'correlation_id' => $e->getCorrelationId(),
    ]);

    throw $e;
}
```

## Token caching

The SDK requests a bearer token on the first API call and refreshes it before expiry. This package stores that token in Laravel's cache instead of in process memory, and the cache entry expires exactly when the token does.

That matters as soon as your application runs more than one PHP process — web workers, queue workers, scheduled commands. With per-process storage each of them authenticates separately, and keeps re-authenticating after every deploy or worker restart. With a **shared** store they issue one token between them.

```php
'token_cache' => [
    'store' => env('CHECK_COMMERCE_TOKEN_CACHE_STORE'),   // null uses your default cache store
    'prefix' => env('CHECK_COMMERCE_TOKEN_CACHE_PREFIX', 'check-commerce'),
],
```

Use `redis`, `memcached` or `database` in production. The `file` store works on a single server; `array` is per-request and effectively disables sharing. The cache key is derived from your base URL, merchant number, API key and scopes, so multiple merchants in one application never collide.

Warm the token at deploy time — and fail loudly on bad credentials — with:

```php
CheckCommerce::authenticate();
```

## Testing

`CheckCommerce::fake()` swaps the transport for a recording PSR-18 client. Everything else — your config, the container binding, the token store — stays exactly as it is in production:

```php
use CheckCommerce\Laravel\Facades\CheckCommerce;
use Psr\Http\Message\RequestInterface;

it('charges the customer at checkout', function () {
    $checkCommerce = CheckCommerce::fake();

    $checkCommerce->queueJson(200, [
        'transactionId' => 123456789,
        'status' => 'Processed',
    ]);

    $this->post('/checkout', ['amount' => 42.50])->assertRedirect();

    $checkCommerce->assertSentCount(1);
    $checkCommerce->assertSent(fn (RequestInterface $request): bool => json_decode(
        (string) $request->getBody(), true
    )['request']['amount'] === 42.50);
});
```

- Responses are replayed in the order they are queued; `queue()` also accepts a `Throwable` to simulate a transport failure.
- Queue a non-2xx response to exercise your error handling — the SDK still maps it to its typed exception.
- The fake answers the SDK's authentication call itself, so the queue and the recorded requests only ever hold the calls your application made. Call `withoutAuthentication()` when the authentication request is what you are testing.
- Credentials are stubbed when your testing environment has none, so `phpunit.xml` needs no Check Commerce variables.
- Assertions: `assertSent()`, `assertNotSent()`, `assertSentCount()`, `assertNothingSent()`. Raw requests are on `$checkCommerce->requests`, with `lastRequest()` and `requestCount()` as shortcuts.

## Replacing the HTTP client

Bind a PSR-18 client (or PSR-17 factories) in the container and the SDK uses it — handy for proxies, logging middleware or a shared connection pool:

```php
$this->app->bind(\Psr\Http\Client\ClientInterface::class, fn () => new \GuzzleHttp\Client([
    'handler' => $stackWithYourMiddleware,
]));
```

Set `check-commerce.http_client.from_container` to `false` to ignore container bindings and let the SDK discover and build its own client.

## Contributing

```bash
composer install
composer test     # static analysis, formatting, type coverage and tests
composer lint     # apply formatting
composer build    # build the Testbench workbench
composer serve    # serve the workbench app
```

The package develops against [Orchestra Testbench](https://packages.tools/testbench). `composer serve` boots a throwaway Laravel application from `workbench/` with the package already registered, which is the quickest way to try a change against a real app.

See our [contributing guide](.github/CONTRIBUTING.md) to get started.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Timothy Wood](https://github.com/codearachnid)
- [All Contributors](../../contributors)

## License

Check Commerce for Laravel is open-sourced software licensed under the [MIT license](LICENSE.md).
