<?php

declare(strict_types=1);

namespace App\Modules\Reservation\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

final class ReservationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $moduleRoot = dirname(__DIR__, 2);

        $this->loadMigrationsFrom($moduleRoot.'/Infrastructure/Persistence/Migrations');
    }
}
