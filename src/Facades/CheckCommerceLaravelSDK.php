<?php

declare(strict_types=1);

namespace CheckCommerceLaravelSDK\CheckCommerceLaravelSDK\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \CheckCommerceLaravelSDK\CheckCommerceLaravelSDK\CheckCommerceLaravelSDK
 */
class CheckCommerceLaravelSDK extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CheckCommerceLaravelSDK\CheckCommerceLaravelSDK\CheckCommerceLaravelSDK::class;
    }
}
