<?php

declare(strict_types=1);

namespace CheckCommerce\Laravel\Auth;

use CheckCommerce\Auth\AccessToken;
use CheckCommerce\Auth\TokenStoreInterface;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository;

/**
 * Stores Check Commerce bearer tokens in Laravel's cache.
 *
 * Every process resolving the client shares one token instead of
 * authenticating on its own, which matters as soon as an application runs more
 * than one PHP worker. Point this at a shared store (redis, memcached,
 * database) for that to hold across servers.
 */
class CacheTokenStore implements TokenStoreInterface
{
    /**
     * @param  string|null  $store  cache store name, or null for the default store
     * @param  string  $prefix  prepended to the key the SDK derives from the credentials
     */
    public function __construct(
        protected CacheFactory $cache,
        protected ?string $store = null,
        protected string $prefix = 'check-commerce',
    ) {}

    public function get(string $key): ?AccessToken
    {
        $data = $this->store()->get($this->key($key));

        return is_array($data) ? AccessToken::fromArray($data) : null;
    }

    /**
     * Caches the token until it expires.
     *
     * The lifetime comes from the token itself, so a cached token is never
     * handed out after the API stops honoring it. Tokens the API issues
     * without an expiry are kept until they are explicitly forgotten.
     */
    public function put(string $key, AccessToken $token): void
    {
        if ($token->expiresAt === null) {
            $this->store()->forever($this->key($key), $token->toArray());

            return;
        }

        $this->store()->put($this->key($key), $token->toArray(), $token->expiresAt);
    }

    public function forget(string $key): void
    {
        $this->store()->forget($this->key($key));
    }

    protected function store(): Repository
    {
        return $this->cache->store($this->store);
    }

    protected function key(string $key): string
    {
        return $this->prefix === '' ? $key : $this->prefix.':'.$key;
    }
}
