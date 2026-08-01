<div align="center">
    <h1>Check Commerce Laravel Sdk</h1>
</div>

<p align="center">
    <a href="https://packagist.org/packages/codearachnid/check-commerce-laravel-sdk"><img src="https://img.shields.io/packagist/v/codearachnid/check-commerce-laravel-sdk.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/codearachnid/check-commerce-laravel-sdk"><img src="https://img.shields.io/packagist/php-v/codearachnid/check-commerce-laravel-sdk.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://packagist.org/packages/codearachnid/check-commerce-laravel-sdk"><img src="https://badge.laravel.cloud/badge/codearachnid/check-commerce-laravel-sdk?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/codearachnid/check-commerce-laravel-sdk/actions"><img alt="GitHub Workflow Status (main)" src="https://img.shields.io/github/actions/workflow/status/codearachnid/check-commerce-laravel-sdk/tests.yml?branch=main&label=Tests&style=flat-square"></a>
    <a href="https://packagist.org/packages/codearachnid/check-commerce-laravel-sdk"><img src="https://img.shields.io/packagist/dt/codearachnid/check-commerce-laravel-sdk.svg?style=flat-square" alt="Total Downloads"></a>
</p>

A modern PHP SDK for the Check Commerce (OBP Link) API — ACH, RTP, paper check and IAT payments, stored consumers, recurring subscriptions, hosted payment pages, batch processing and merchant boarding.

## Installation

You can install the package via Composer:

```bash
composer require codearachnid/check-commerce-laravel-sdk
```

You may publish all of the package's resources at once:

```bash
php artisan vendor:publish --tag="check-commerce-laravel-sdk"
```

Or, you may publish each resource individually:

### Publishing the Configuration File

```bash
php artisan vendor:publish --tag="check-commerce-laravel-sdk-config"
```

### Publishing and Running the Migrations

```bash
php artisan vendor:publish --tag="check-commerce-laravel-sdk-migrations"
php artisan migrate
```

### Publishing the Views

```bash
php artisan vendor:publish --tag="check-commerce-laravel-sdk-views"
```

### Publishing the Translations

```bash
php artisan vendor:publish --tag="check-commerce-laravel-sdk-lang"
```

### Publishing the Public Assets

```bash
php artisan vendor:publish --tag="check-commerce-laravel-sdk-assets"
```

## Usage

<!-- Add a basic usage example here. -->

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Thank you for considering contributing to Check Commerce Laravel Sdk! Please review our [contributing guide](.github/CONTRIBUTING.md) to get started.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Timothy Wood](https://github.com/codearachnid)
- [All Contributors](../../contributors)

## License

Check Commerce Laravel Sdk is open-sourced software licensed under the [MIT license](LICENSE.md).
