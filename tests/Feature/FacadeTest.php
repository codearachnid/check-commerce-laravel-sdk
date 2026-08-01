<?php

declare(strict_types=1);

use CheckCommerce\CheckCommerceClient;
use CheckCommerce\Laravel\Facades\CheckCommerce;
use CheckCommerce\Service\BatchService;
use CheckCommerce\Service\BoardingService;
use CheckCommerce\Service\ConsumerService;
use CheckCommerce\Service\HostedPageService;
use CheckCommerce\Service\SubscriptionService;
use CheckCommerce\Service\TransactionService;

beforeEach(function () {
    config()->set('check-commerce.api_key', 'test-api-key');
    config()->set('check-commerce.merchant_number', '999997');
});

it('resolves the container client', function () {
    expect(CheckCommerce::getFacadeRoot())->toBe(app(CheckCommerceClient::class));
});

it('reaches every service the sdk exposes', function () {
    expect(CheckCommerce::transactions())->toBeInstanceOf(TransactionService::class)
        ->and(CheckCommerce::consumers())->toBeInstanceOf(ConsumerService::class)
        ->and(CheckCommerce::subscriptions())->toBeInstanceOf(SubscriptionService::class)
        ->and(CheckCommerce::batches())->toBeInstanceOf(BatchService::class)
        ->and(CheckCommerce::hostedPages())->toBeInstanceOf(HostedPageService::class)
        ->and(CheckCommerce::boarding())->toBeInstanceOf(BoardingService::class);
});

it('returns the same service instances as the client', function () {
    expect(CheckCommerce::transactions())->toBe(app(CheckCommerceClient::class)->transactions);
});

it('forwards everything else to the client', function () {
    expect(fn () => CheckCommerce::thisMethodDoesNotExist())->toThrow(Error::class);
});
