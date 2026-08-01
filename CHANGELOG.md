# Release Notes

## [Unreleased](https://github.com/codearachnid/check-commerce-laravel-sdk/compare/v0.1.0...1.x)


## [v0.1.0](https://github.com/codearachnid/check-commerce-laravel-sdk/releases/tag/v0.1.0) - 2026-08-01

Initial pre-release: the Laravel integration for [`codearachnid/check-commerce-php-sdk`](https://github.com/codearachnid/check-commerce-php-sdk).

### Added

- `CheckCommerceServiceProvider`, registering `CheckCommerce\CheckCommerceClient` as a singleton aliased to `check-commerce`, built from the package config and failing with a message naming the missing variable when credentials are absent.
- Publishable `config/check-commerce.php`, mapping the `CHECK_COMMERCE_*` environment variables onto every SDK configuration option, plus the token cache and HTTP client settings.
- `CheckCommerce` facade over the client, exposing the SDK services as `transactions()`, `consumers()`, `subscriptions()`, `batches()`, `hostedPages()` and `boarding()`, plus `authenticate()`.
- `CacheTokenStore`, a `TokenStoreInterface` implementation backed by Laravel's cache, with the entry lifetime taken from the token's own expiry.
- PSR-18 client and PSR-17 factory resolution from the container, so applications can replace the SDK's transport; toggled by `check-commerce.http_client.from_container`.
- `CheckCommerce::fake()` and `FakeHttpClient` for queueing responses and asserting requests in application feature tests.
