<?php

declare(strict_types=1);

use CheckCommerce\Auth\AccessToken;
use CheckCommerce\Laravel\Auth\CacheTokenStore;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Support\Facades\Cache;

function tokenStore(?string $store = null, string $prefix = 'check-commerce'): CacheTokenStore
{
    return new CacheTokenStore(app(CacheFactory::class), $store, $prefix);
}

function tokenExpiringIn(string $modifier): AccessToken
{
    return new AccessToken(
        token: 'test-jwt-token',
        tokenId: 'c0a80121-7ac0-4e1c-9a0f-0a1b2c3d4e5f',
        expiresAt: new DateTimeImmutable($modifier, new DateTimeZone('UTC')),
    );
}

it('round-trips a token through the cache', function () {
    $store = tokenStore();
    $stored = tokenExpiringIn('+1 hour');
    $store->put('token-key', $stored);

    $token = $store->get('token-key');

    expect($token)->toBeInstanceOf(AccessToken::class)
        ->and($token->token)->toBe('test-jwt-token')
        ->and($token->tokenId)->toBe('c0a80121-7ac0-4e1c-9a0f-0a1b2c3d4e5f')
        ->and($token->expiresAt?->getTimestamp())->toBe($stored->expiresAt?->getTimestamp());
});

it('returns null when no token is cached', function () {
    expect(tokenStore()->get('token-key'))->toBeNull();
});

it('caches a token only for as long as it lives', function () {
    $store = tokenStore();
    $store->put('token-key', tokenExpiringIn('+10 minutes'));

    $this->travel(9)->minutes();
    expect($store->get('token-key'))->toBeInstanceOf(AccessToken::class);

    $this->travel(2)->minutes();
    expect($store->get('token-key'))->toBeNull();
});

it('does not cache an already expired token', function () {
    $store = tokenStore();
    $store->put('token-key', tokenExpiringIn('-1 minute'));

    expect($store->get('token-key'))->toBeNull();
});

it('keeps a token without an expiry until it is forgotten', function () {
    $store = tokenStore();
    $store->put('token-key', new AccessToken(token: 'test-jwt-token'));

    $this->travel(1)->year();

    expect($store->get('token-key'))->toBeInstanceOf(AccessToken::class);
});

it('forgets a cached token', function () {
    $store = tokenStore();
    $store->put('token-key', tokenExpiringIn('+1 hour'));

    $store->forget('token-key');

    expect($store->get('token-key'))->toBeNull();
});

it('prefixes the key the sdk derives from the credentials', function () {
    tokenStore(prefix: 'tenant-a')->put('token-key', tokenExpiringIn('+1 hour'));

    expect(Cache::get('tenant-a:token-key'))->toBeArray()
        ->and(Cache::get('token-key'))->toBeNull();
});

it('writes to the named cache store when one is configured', function () {
    config()->set('cache.stores.tokens', ['driver' => 'array']);

    tokenStore(store: 'tokens')->put('token-key', tokenExpiringIn('+1 hour'));

    expect(Cache::store('tokens')->get('check-commerce:token-key'))->toBeArray()
        ->and(Cache::store('array')->get('check-commerce:token-key'))->toBeNull();
});

it('writes to the default cache store when none is configured', function () {
    config()->set('cache.default', 'array');

    tokenStore()->put('token-key', tokenExpiringIn('+1 hour'));

    expect(Cache::store('array')->get('check-commerce:token-key'))->toBeArray();
});
