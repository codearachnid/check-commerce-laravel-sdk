---
name: check-commerce-laravel-sdk-development
description: >
  Configure and apply the Check Commerce Laravel SDK package in Laravel applications.
license: MIT
metadata:
  author: Timothy Wood
---

# Check Commerce Laravel SDK

Use this skill when a Laravel application needs to take payments through Check Commerce (OBP Link) with the `codearachnid/check-commerce-laravel-sdk` package.

## Primary Goal

- apply the `codearachnid/check-commerce-laravel-sdk` package's public API in the smallest correct way

## Workflow

### 1. Inspect the Laravel app context

- confirm the app is a Laravel project with the package installed
- confirm a PSR-18 client is installed (`guzzlehttp/guzzle` unless another one is present)
- inspect the target code paths where payments should be taken

### 2. Configure the package

- publish the config only when a value must be changed in code: `php artisan vendor:publish --tag="check-commerce-laravel-sdk-config"`
- set `CHECK_COMMERCE_API_KEY`, `CHECK_COMMERCE_MERCHANT_NUMBER` and `CHECK_COMMERCE_ENVIRONMENT` (`sandbox` or `production`) in `.env`
- set `CHECK_COMMERCE_TOKEN_CACHE_STORE` to a shared cache store (`redis`, `memcached`, `database`) when the app runs more than one process; bearer tokens are cached there and expire with the token
- leave `check-commerce.http_client.from_container` alone unless the app binds its own PSR-18 client or PSR-17 factories

### 3. Apply the package's public API

- inject `CheckCommerce\CheckCommerceClient` and read services as properties: `$client->transactions`, `consumers`, `subscriptions`, `batches`, `hostedPages`, `boarding`
- or call the `CheckCommerce\Laravel\Facades\CheckCommerce` facade, where the same services are methods: `CheckCommerce::transactions()`, `CheckCommerce::consumers()`, and so on, plus `CheckCommerce::authenticate()`
- pass request payloads straight through to the SDK services in the API's own camelCase spelling; do not build wrapper classes around them
- catch the SDK's typed exceptions from `CheckCommerce\Exception\*` (`ValidationException`, `RateLimitException`, `ApiException`, `TransportException`, ...) rather than inspecting status codes

### 4. Test the integration

- call `CheckCommerce::fake()` in the test, queue responses with `queueJson()`, and assert with `assertSent()`, `assertNotSent()`, `assertSentCount()` or `assertNothingSent()`
- the fake answers the SDK's authentication request itself, so queue only the responses the application's own calls need
- no Check Commerce credentials are needed in the testing environment

## Rules, References, and Templates

Read before executing:

- `README.md` in the package for one runnable example per service
- `config/check-commerce.php` in the package; every key is documented in place

## Examples

Charge a bank account during checkout:

```php
use CheckCommerce\Exception\ValidationException;
use CheckCommerce\Laravel\Facades\CheckCommerce;

try {
    $result = CheckCommerce::transactions()->debit([
        'merchantNumber' => config('check-commerce.merchant_number'),
        'amount' => 42.50,
        'referenceNumber' => $order->number,
        'consumerInfo' => [
            'name' => $order->customer_name,
            'bankAccountNumber' => $request->string('account_number')->toString(),
            'bankRoutingNumber' => $request->integer('routing_number'),
        ],
    ]);
} catch (ValidationException $e) {
    return back()->withErrors(collect($e->getValidationErrors())
        ->mapWithKeys(fn ($error) => [$error->property => $error->detail])
        ->all());
}

$order->update(['transaction_id' => $result->transactionId]);
```

Cover it with a feature test:

```php
use CheckCommerce\Laravel\Facades\CheckCommerce;

it('charges the customer at checkout', function () {
    $checkCommerce = CheckCommerce::fake();
    $checkCommerce->queueJson(200, ['transactionId' => 123456789, 'status' => 'Processed']);

    $this->post('/checkout', [...])->assertRedirect();

    $checkCommerce->assertSentCount(1);
});
```

## Anti-patterns

- do not wrap the SDK's services, resources or exceptions in app-level abstractions before there is a second consumer
- do not build `CheckCommerceClient` by hand; resolve it from the container so config, token caching and transport bindings apply
- do not store bank account or routing numbers in the app; store the `consumerId` a `consumers()->create()` call returns and reference it in later transactions
- do not call `authenticate()` before each request; the SDK acquires and refreshes tokens on its own
- do not use HTTP mocking libraries in tests; use `CheckCommerce::fake()`
- do not document package internals here; keep the skill focused on adoption in Laravel apps
