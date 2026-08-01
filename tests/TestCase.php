<?php

declare(strict_types=1);

namespace CheckCommerce\Laravel\Tests;

use CheckCommerce\Laravel\CheckCommerceServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            CheckCommerceServiceProvider::class,
        ];
    }
}
