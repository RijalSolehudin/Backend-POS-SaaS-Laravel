<?php

declare(strict_types=1);

use App\Modules\Sales\Presentation\Http\Web\Controllers\TenantOrderVoidController;
use App\Modules\Sales\Presentation\Http\Web\Controllers\TenantSalesSummaryController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/tenants/{tenant}/sales')
    ->name('tenant.sales.')
    ->where(['tenant' => '[0-9a-hjkmnp-tv-z]{26}'])
    ->middleware(['web', 'tenant.authenticated', 'tenant.context', 'tenant.password-current', 'tenant.owner'])
    ->group(function (): void {
        Route::get('daily', TenantSalesSummaryController::class)->name('daily');
        Route::post('orders/{order}/void', TenantOrderVoidController::class)
            ->where('order', '[0-9a-hjkmnp-tv-z]{26}')
            ->name('orders.void')
            ->block();
    });
