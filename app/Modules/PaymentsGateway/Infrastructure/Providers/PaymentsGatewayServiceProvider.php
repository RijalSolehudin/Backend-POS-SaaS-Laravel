<?php

declare(strict_types=1);

namespace App\Modules\PaymentsGateway\Infrastructure\Providers;

use App\Modules\PaymentsGateway\Application\Contracts\PaymentProvider;
use App\Modules\PaymentsGateway\Infrastructure\PaymentProviders\FakePaymentProvider;
use Illuminate\Support\ServiceProvider;

final class PaymentsGatewayServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentProvider::class, FakePaymentProvider::class);
    }

    public function boot(): void
    {
        $moduleRoot = dirname(__DIR__, 2);

        $this->loadMigrationsFrom($moduleRoot.'/Infrastructure/Persistence/Migrations');
    }
}
