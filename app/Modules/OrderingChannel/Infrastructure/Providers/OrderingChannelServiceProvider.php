<?php

declare(strict_types=1);

namespace App\Modules\OrderingChannel\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

final class OrderingChannelServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $moduleRoot = dirname(__DIR__, 2);

        $this->loadMigrationsFrom($moduleRoot.'/Infrastructure/Persistence/Migrations');
        $this->loadRoutesFrom($moduleRoot.'/Presentation/Http/Routes/api.php');
    }
}
