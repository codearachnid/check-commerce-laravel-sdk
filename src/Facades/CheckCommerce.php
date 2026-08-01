<?php

declare(strict_types=1);

namespace CheckCommerce\Laravel\Facades;

use CheckCommerce\CheckCommerceClient;
use CheckCommerce\Laravel\Testing\FakeHttpClient;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\Facade;
use Psr\Http\Client\ClientInterface;

/**
 * @method static \CheckCommerce\Service\TransactionService transactions()
 * @method static \CheckCommerce\Service\ConsumerService consumers()
 * @method static \CheckCommerce\Service\SubscriptionService subscriptions()
 * @method static \CheckCommerce\Service\BatchService batches()
 * @method static \CheckCommerce\Service\HostedPageService hostedPages()
 * @method static \CheckCommerce\Service\BoardingService boarding()
 * @method static \CheckCommerce\Auth\AccessToken authenticate()
 *
 * @see CheckCommerceClient
 */
class CheckCommerce extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CheckCommerceClient::class;
    }

    /**
     * Replaces the Check Commerce transport with a fake for the current test.
     *
     * The client is rebuilt from the application's own configuration, so
     * everything the package wires up still applies — only the PSR-18 client
     * is swapped. Credentials are stubbed when the test environment has none,
     * and the fake answers authentication itself so tests only queue the
     * responses they care about.
     *
     * ```php
     * $checkCommerce = CheckCommerce::fake();
     * $checkCommerce->queueJson(200, ['transactionId' => 123456789, 'status' => 'Processed']);
     *
     * $this->post('/checkout', [...]);
     *
     * $checkCommerce->assertSent(fn ($request) => $request->getUri()->getPath() === '/api/transaction/ach');
     * ```
     */
    public static function fake(?FakeHttpClient $http = null): FakeHttpClient
    {
        $http ??= new FakeHttpClient;

        $config = app(ConfigRepository::class);

        $config->set('check-commerce.http_client.from_container', true);

        foreach (['api_key' => 'fake-api-key', 'merchant_number' => '000000'] as $key => $placeholder) {
            if (blank($config->get('check-commerce.'.$key))) {
                $config->set('check-commerce.'.$key, $placeholder);
            }
        }

        app()->instance(ClientInterface::class, $http);
        app()->forgetInstance(CheckCommerceClient::class);

        static::clearResolvedInstance(static::getFacadeAccessor());

        return $http;
    }

    /**
     * The SDK exposes its services as readonly properties, and PHP has no
     * static property proxy, so the facade reads them through the same call it
     * forwards methods with. Everything else goes straight to the client.
     *
     * @param  string  $method
     * @param  array<int, mixed>  $args
     */
    public static function __callStatic($method, $args): mixed
    {
        $client = static::getFacadeRoot();

        return match (true) {
            ! $client instanceof CheckCommerceClient => parent::__callStatic($method, $args),
            $method === 'transactions' => $client->transactions,
            $method === 'consumers' => $client->consumers,
            $method === 'subscriptions' => $client->subscriptions,
            $method === 'batches' => $client->batches,
            $method === 'hostedPages' => $client->hostedPages,
            $method === 'boarding' => $client->boarding,
            default => parent::__callStatic($method, $args),
        };
    }
}
