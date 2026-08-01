<?php

declare(strict_types=1);

namespace CheckCommerce\Laravel\Testing;

use CheckCommerce\Laravel\Facades\CheckCommerce;
use DateTimeImmutable;
use DateTimeZone;
use Http\Discovery\Psr17FactoryDiscovery;
use LogicException;
use PHPUnit\Framework\Assert;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Throwable;

/**
 * PSR-18 client that replays queued responses and records every request.
 *
 * Install it with {@see CheckCommerce::fake()}
 * rather than building it by hand.
 */
class FakeHttpClient implements ClientInterface
{
    /**
     * Every request sent through the fake, except authentication.
     *
     * @var list<RequestInterface>
     */
    public array $requests = [];

    /**
     * Authentication requests, which the fake answers on its own.
     *
     * @var list<RequestInterface>
     */
    public array $authentications = [];

    /** @var list<ResponseInterface|Throwable> */
    protected array $queue = [];

    protected bool $authenticates = true;

    protected ResponseFactoryInterface $responseFactory;

    protected StreamFactoryInterface $streamFactory;

    public function __construct(
        ?ResponseFactoryInterface $responseFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
    ) {
        $this->responseFactory = $responseFactory ?? Psr17FactoryDiscovery::findResponseFactory();
        $this->streamFactory = $streamFactory ?? Psr17FactoryDiscovery::findStreamFactory();
    }

    /**
     * Queues a response, or an exception for the client to throw.
     */
    public function queue(ResponseInterface|Throwable $response): static
    {
        $this->queue[] = $response;

        return $this;
    }

    /**
     * Queues a JSON response, the shape every Check Commerce endpoint returns.
     *
     * @param  array<string, mixed>  $body
     * @param  array<string, string>  $headers
     */
    public function queueJson(int $status, array $body = [], array $headers = []): static
    {
        return $this->queue($this->jsonResponse($status, $body, $headers));
    }

    /**
     * Stops the fake from answering authentication on its own, so queued
     * responses cover the authentication request too.
     */
    public function withoutAuthentication(): static
    {
        $this->authenticates = false;

        return $this;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        if ($this->authenticates && $this->isAuthentication($request)) {
            $this->authentications[] = $request;

            return $this->jsonResponse(200, [
                'tokenId' => 'c0a80121-7ac0-4e1c-9a0f-0a1b2c3d4e5f',
                'token' => 'fake-access-token',
                'expiresAtUTC' => (new DateTimeImmutable('+1 hour', new DateTimeZone('UTC')))->format('Y-m-d\TH:i:s\Z'),
            ]);
        }

        $this->requests[] = $request;

        if ($this->queue === []) {
            throw new LogicException(sprintf(
                'No Check Commerce response queued for %s %s.',
                $request->getMethod(),
                (string) $request->getUri(),
            ));
        }

        $next = array_shift($this->queue);

        if ($next instanceof Throwable) {
            throw $next;
        }

        return $next;
    }

    public function lastRequest(): RequestInterface
    {
        if ($this->requests === []) {
            throw new LogicException('No Check Commerce request has been sent.');
        }

        return $this->requests[array_key_last($this->requests)];
    }

    public function requestCount(): int
    {
        return count($this->requests);
    }

    /**
     * @param  callable(RequestInterface): bool  $callback
     */
    public function assertSent(callable $callback): static
    {
        Assert::assertTrue(
            $this->sent($callback),
            'The expected Check Commerce request was not sent.',
        );

        return $this;
    }

    /**
     * @param  callable(RequestInterface): bool  $callback
     */
    public function assertNotSent(callable $callback): static
    {
        Assert::assertFalse(
            $this->sent($callback),
            'An unexpected Check Commerce request was sent.',
        );

        return $this;
    }

    public function assertSentCount(int $count): static
    {
        Assert::assertCount($count, $this->requests, sprintf(
            'Expected %d Check Commerce requests, %d were sent.',
            $count,
            $this->requestCount(),
        ));

        return $this;
    }

    public function assertNothingSent(): static
    {
        return $this->assertSentCount(0);
    }

    /**
     * @param  callable(RequestInterface): bool  $callback
     */
    protected function sent(callable $callback): bool
    {
        foreach ($this->requests as $request) {
            if ($callback($request)) {
                return true;
            }
        }

        return false;
    }

    protected function isAuthentication(RequestInterface $request): bool
    {
        return $request->getMethod() === 'POST'
            && str_ends_with($request->getUri()->getPath(), '/authenticate');
    }

    /**
     * @param  array<string, mixed>  $body
     * @param  array<string, string>  $headers
     */
    protected function jsonResponse(int $status, array $body, array $headers = []): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($status)
            ->withBody($this->streamFactory->createStream(json_encode($body, JSON_THROW_ON_ERROR)))
            ->withHeader('Content-Type', 'application/json');

        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }
}
