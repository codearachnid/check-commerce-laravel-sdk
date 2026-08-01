<?php

declare(strict_types=1);

namespace CheckCommerce\Laravel\Facades;

use CheckCommerce\CheckCommerceClient;
use Illuminate\Support\Facades\Facade;

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
