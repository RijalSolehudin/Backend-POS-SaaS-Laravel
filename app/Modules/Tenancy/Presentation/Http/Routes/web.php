<?php

declare(strict_types=1);

use App\Modules\Tenancy\Presentation\Http\Web\Controllers\PlatformTenantController;
use App\Modules\Tenancy\Presentation\Http\Web\Controllers\TenantHomeController;
use Illuminate\Support\Facades\Route;

Route::prefix('platform')
    ->name('platform.')
    ->middleware(['platform.web', 'platform.authenticated'])
    ->group(function (): void {
        Route::get('tenants', [PlatformTenantController::class, 'index'])->name('tenants.index');
        Route::get('tenants/create', [PlatformTenantController::class, 'create'])
            ->middleware('platform.confirmed')
            ->name('tenants.create');
        Route::post('tenants', [PlatformTenantController::class, 'store'])
            ->middleware('platform.confirmed')
            ->name('tenants.store')
            ->block();
        Route::get('tenants/{tenant}', [PlatformTenantController::class, 'show'])
            ->where('tenant', '[0-9a-hjkmnp-tv-z]{26}')
            ->name('tenants.show');
        Route::post('tenants/{tenant}/disable', [PlatformTenantController::class, 'disable'])
            ->middleware('platform.confirmed')
            ->where('tenant', '[0-9a-hjkmnp-tv-z]{26}')
            ->name('tenants.disable')
            ->block();
    });

Route::prefix('admin/tenants/{tenant}')
    ->name('tenant.')
    ->where(['tenant' => '[0-9a-hjkmnp-tv-z]{26}'])
    ->middleware(['web', 'tenant.authenticated', 'tenant.context', 'tenant.password-current'])
    ->group(function (): void {
        Route::get('/', TenantHomeController::class)->name('home');
    });
