<?php

declare(strict_types=1);

use CheckCommerce\Auth\TokenStoreInterface;
use CheckCommerce\CheckCommerceClient;
use CheckCommerce\Environment;
use CheckCommerce\Exception\InvalidArgumentException;
use CheckCommerce\Exception\TransportException;
use CheckCommerce\Laravel\Auth\CacheTokenStore;
use CheckCommerce\Scope;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

beforeEach(function () {
    config()->set('check-commerce.api_key', 'test-api-key');
    config()->set('check-commerce.merchant_number', '999997');
});

function recordingHttpClient(): ClientInterface
{
    return new class implements ClientInterface
    {
        public int $calls = 0;

        public function sendRequest(RequestInterface $request): ResponseInterface
        {
            $this->calls++;

            return new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'tokenId' => 'c0a80121-7ac0-4e1c-9a0f-0a1b2c3d4e5f',
                'token' => 'container-token',
                'expiresAtUTC' => (new DateTimeImmutable('+1 hour', new DateTimeZone('UTC')))->format('Y-m-d\TH:i:s\Z'),
            ], JSON_THROW_ON_ERROR));
        }
    };
}

function recordingHttpFactory(): RequestFactoryInterface&StreamFactoryInterface
{
    return new class implements RequestFactoryInterface, StreamFactoryInterface
    {
        public int $calls = 0;

        private HttpFactory $factory;

        public function __construct()
        {
            $this->factory = new HttpFactory;
        }

        public function createRequest(string $method, $uri): RequestInterface
        {
            $this->calls++;

            return $this->factory->createRequest($method, $uri);
        }

        public function createStream(string $content = ''): StreamInterface
        {
            return $this->factory->createStream($content);
        }

        public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
        {
            return $this->factory->createStreamFromFile($filename, $mode);
        }

        public function createStreamFromResource($resource): StreamInterface
        {
            return $this->factory->createStreamFromResource($resource);
        }
    };
}

it('resolves the client as a singleton', function () {
    expect(app(CheckCommerceClient::class))->toBeInstanceOf(CheckCommerceClient::class)
        ->and(app(CheckCommerceClient::class))->toBe(app(CheckCommerceClient::class));
});

it('aliases the client to check-commerce', function () {
    expect(app('check-commerce'))->toBe(app(CheckCommerceClient::class));
});

it('maps every config key onto the sdk configuration', function () {
    config()->set('check-commerce.environment', 'sandbox');
    config()->set('check-commerce.base_url', 'https://proxy.example.com/api/');
    config()->set('check-commerce.scopes', [Scope::Transactions->value, Scope::HostedPages]);
    config()->set('check-commerce.api_version', '2.0');
    config()->set('check-commerce.timeout', 45.5);
    config()->set('check-commerce.connect_timeout', 7.5);
    config()->set('check-commerce.max_retries', 4);
    config()->set('check-commerce.retry_initial_delay_ms', 250);
    config()->set('check-commerce.retry_max_delay_ms', 16000);
    config()->set('check-commerce.token_expiry_margin_seconds', 120);

    $config = app(CheckCommerceClient::class)->config;

    expect($config->apiKey)->toBe('test-api-key')
        ->and($config->merchantNumber)->toBe('999997')
        ->and($config->environment)->toBe(Environment::Sandbox)
        ->and($config->baseUrl)->toBe('https://proxy.example.com/api')
        ->and($config->scopes)->toBe(['obp.tran', 'obp.hp'])
        ->and($config->apiVersion)->toBe('2.0')
        ->and($config->timeout)->toBe(45.5)
        ->and($config->connectTimeout)->toBe(7.5)
        ->and($config->maxRetries)->toBe(4)
        ->and($config->retryInitialDelayMs)->toBe(250)
        ->and($config->retryMaxDelayMs)->toBe(16000)
        ->and($config->tokenExpiryMarginSeconds)->toBe(120);
});

it('uses the sdk defaults for options the config leaves alone', function () {
    $config = app(CheckCommerceClient::class)->config;

    expect($config->environment)->toBe(Environment::Production)
        ->and($config->baseUrl)->toBe('https://checkcommerce.com/api')
        ->and($config->scopes)->toBe([])
        ->and($config->apiVersion)->toBe('1.0')
        ->and($config->timeout)->toBe(30.0)
        ->and($config->connectTimeout)->toBe(10.0)
        ->and($config->maxRetries)->toBe(2)
        ->and($config->retryInitialDelayMs)->toBe(500)
        ->and($config->retryMaxDelayMs)->toBe(8000)
        ->and($config->tokenExpiryMarginSeconds)->toBe(60);
});

it('coerces the string values the environment supplies', function () {
    config()->set('check-commerce.timeout', '45');
    config()->set('check-commerce.max_retries', '0');

    $config = app(CheckCommerceClient::class)->config;

    expect($config->timeout)->toBe(45.0)
        ->and($config->maxRetries)->toBe(0);
});

it('keeps the laravel-only config keys away from the sdk configuration', function () {
    config()->set('check-commerce.token_cache.store', 'array');
    config()->set('check-commerce.http_client.from_container', false);

    expect(app(CheckCommerceClient::class))->toBeInstanceOf(CheckCommerceClient::class);
});

it('fails at resolve time when the api key is missing', function () {
    config()->set('check-commerce.api_key', null);

    app(CheckCommerceClient::class);
})->throws(InvalidArgumentException::class, 'The Check Commerce api key is not configured. Set CHECK_COMMERCE_API_KEY');

it('fails at resolve time when the merchant number is missing', function () {
    config()->set('check-commerce.merchant_number', '  ');

    app(CheckCommerceClient::class);
})->throws(InvalidArgumentException::class, 'The Check Commerce merchant number is not configured. Set CHECK_COMMERCE_MERCHANT_NUMBER');

it('boots without credentials', function () {
    config()->set('check-commerce.api_key', null);
    config()->set('check-commerce.merchant_number', null);

    expect(app()->isBooted())->toBeTrue()
        ->and(app(TokenStoreInterface::class))->toBeInstanceOf(CacheTokenStore::class);
});

it('gives the sdk the cache-backed token store', function () {
    expect(app(TokenStoreInterface::class))->toBeInstanceOf(CacheTokenStore::class)
        ->and(app(TokenStoreInterface::class))->toBe(app(TokenStoreInterface::class));
});

it('hands the sdk the psr-18 client and psr-17 factories bound by the application', function () {
    $http = recordingHttpClient();
    $factory = recordingHttpFactory();

    app()->instance(ClientInterface::class, $http);
    app()->instance(RequestFactoryInterface::class, $factory);
    app()->instance(StreamFactoryInterface::class, $factory);

    $token = app(CheckCommerceClient::class)->authenticate();

    expect($token->token)->toBe('container-token')
        ->and($http->calls)->toBe(1)
        ->and($factory->calls)->toBe(1);
});

it('leaves transport to the sdk when container resolution is disabled', function () {
    config()->set('check-commerce.http_client.from_container', false);
    config()->set('check-commerce.base_url', 'http://127.0.0.1:1');
    config()->set('check-commerce.connect_timeout', 1);
    config()->set('check-commerce.max_retries', 0);

    $http = recordingHttpClient();
    app()->instance(ClientInterface::class, $http);

    expect(fn () => app(CheckCommerceClient::class)->authenticate())
        ->toThrow(TransportException::class)
        ->and($http->calls)->toBe(0);
});
