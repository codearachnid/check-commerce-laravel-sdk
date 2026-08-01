<?php

declare(strict_types=1);

namespace CheckCommerceLaravelSDK\CheckCommerceLaravelSDK\Console\Commands;

use Illuminate\Console\Command;

class CheckCommerceLaravelSDKCommand extends Command
{
    /**
     * The command signature.
     */
    protected $signature = 'check-commerce-laravel-sdk:placeholder';

    /**
     * The command description.
     */
    protected $description = 'Placeholder Artisan command shipped by the package check-commerce-laravel-sdk.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->line('CheckCommerceLaravelSDK placeholder command executed.');

        return self::SUCCESS;
    }
}
