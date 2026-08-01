<?php

declare(strict_types=1);

use CheckCommerce\CheckCommerceClient;
use CheckCommerce\Enums\TransactionStatus;
use CheckCommerce\Exception\ValidationException;
use CheckCommerce\Laravel\Facades\CheckCommerce;
use CheckCommerce\Laravel\Testing\FakeHttpClient;
use PHPUnit\Framework\AssertionFailedError;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

function debitPayload(): array
{
    return [
        'merchantNumber' => '999997',
        'amount' => 42.50,
        'referenceNumber' => 'INV-1001',
        'consumerInfo' => [
            'name' => 'Jane Doe',
            'bankAccountNumber' => '1234567890',
            'bankRoutingNumber' => 121000248,
        ],
    ];
}

it('swaps the container client for the fake', function () {
    $fake = CheckCommerce::fake();

    expect($fake)->toBeInstanceOf(FakeHttpClient::class)
        ->and(app(ClientInterface::class))->toBe($fake)
        ->and(app(CheckCommerceClient::class))->toBeInstanceOf(CheckCommerceClient::class);
});

it('works without any credentials configured', function () {
    config()->set('check-commerce.api_key', null);
    config()->set('check-commerce.merchant_number', null);

    CheckCommerce::fake()->queueJson(200, ['transactionId' => 123456789, 'status' => 'Processed']);

    expect(CheckCommerce::transactions()->debit(debitPayload())->transactionId)->toBe(123456789);
});

it('replays queued responses through the sdk services', function () {
    $fake = CheckCommerce::fake();
    $fake->queueJson(200, [
        'transactionId' => 123456789,
        'status' => 'Processed',
        'correlationId' => 'e7d0c1a2',
    ]);

    $result = CheckCommerce::transactions()->debit(debitPayload());

    expect($result->transactionId)->toBe(123456789)
        ->and($result->status)->toBe(TransactionStatus::Processed)
        ->and($result->correlationId)->toBe('e7d0c1a2');
});

it('answers authentication on its own', function () {
    $fake = CheckCommerce::fake();
    $fake->queueJson(200, ['transactionId' => 123456789, 'status' => 'Processed']);

    CheckCommerce::transactions()->debit(debitPayload());

    expect($fake->authentications)->toHaveCount(1)
        ->and($fake->requestCount())->toBe(1)
        ->and($fake->lastRequest()->getUri()->getPath())->toBe('/api/transaction');
});

it('lets the queue answer authentication when asked to', function () {
    $fake = CheckCommerce::fake()->withoutAuthentication();
    $fake->queueJson(200, [
        'tokenId' => 'c0a80121-7ac0-4e1c-9a0f-0a1b2c3d4e5f',
        'token' => 'queued-token',
        'expiresAtUTC' => (new DateTimeImmutable('+1 hour', new DateTimeZone('UTC')))->format('Y-m-d\TH:i:s\Z'),
    ]);

    expect(CheckCommerce::authenticate()->token)->toBe('queued-token')
        ->and($fake->authentications)->toBeEmpty()
        ->and($fake->requestCount())->toBe(1);
});

it('records the requests the application sent', function () {
    $fake = CheckCommerce::fake();
    $fake->queueJson(200, ['transactionId' => 123456789, 'status' => 'Processed']);

    CheckCommerce::transactions()->debit(debitPayload());

    $request = $fake->lastRequest();
    $body = json_decode((string) $request->getBody(), true);

    expect($request->getMethod())->toBe('POST')
        ->and($body['paymentType'])->toBe('ACH')
        ->and($body['request']['amount'])->toBe(42.50)
        ->and($body['request']['referenceNumber'])->toBe('INV-1001');
});

it('asserts what was and was not sent', function () {
    $fake = CheckCommerce::fake();
    $fake->queueJson(200, ['transactionId' => 123456789, 'status' => 'Processed']);

    CheckCommerce::transactions()->debit(debitPayload());

    $fake->assertSentCount(1)
        ->assertSent(fn (RequestInterface $request): bool => $request->getUri()->getPath() === '/api/transaction')
        ->assertNotSent(fn (RequestInterface $request): bool => $request->getUri()->getPath() === '/api/consumers');
});

it('fails the assertion when the expected request was never sent', function () {
    $fake = CheckCommerce::fake();

    $fake->assertSent(fn (RequestInterface $request): bool => true);
})->throws(AssertionFailedError::class, 'The expected Check Commerce request was not sent.');

it('asserts that nothing was sent', function () {
    CheckCommerce::fake()->assertNothingSent();
});

it('surfaces queued error responses as sdk exceptions', function () {
    $fake = CheckCommerce::fake();
    $fake->queueJson(400, [
        'title' => 'Validation failed',
        'errors' => [['property' => 'amount', 'detail' => 'Amount must be greater than zero.']],
    ]);

    expect(fn () => CheckCommerce::transactions()->debit(debitPayload()))
        ->toThrow(ValidationException::class)
        ->and($fake->requestCount())->toBe(1);
});

it('throws a queued exception', function () {
    $fake = CheckCommerce::fake();
    $fake->queue(new RuntimeException('the network went away'));

    expect(fn () => CheckCommerce::transactions()->debit(debitPayload()))
        ->toThrow(RuntimeException::class, 'the network went away');
});

it('explains which request had no queued response', function () {
    CheckCommerce::fake();

    CheckCommerce::transactions()->debit(debitPayload());
})->throws(LogicException::class, 'No Check Commerce response queued for POST');
