<?php

declare(strict_types=1);

use App\Modules\Tenancy\Presentation\Http\Web\Controllers\PlatformTenantController;
use App\Modules\Tenancy\Presentation\Http\Web\Controllers\TenantHomeController;
use App\Modules\Tenancy\Presentation\Http\Web\Controllers\TenantOutletController;
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

        Route::middleware('tenant.owner')->group(function (): void {
            Route::get('outlets', [TenantOutletController::class, 'index'])->name('outlets.index');
            Route::get('outlets/create', [TenantOutletController::class, 'create'])->name('outlets.create');
            Route::post('outlets', [TenantOutletController::class, 'store'])->name('outlets.store')->block();
            Route::get('outlets/{outlet}/edit', [TenantOutletController::class, 'edit'])
                ->where('outlet', '[0-9a-hjkmnp-tv-z]{26}')
                ->name('outlets.edit');
            Route::put('outlets/{outlet}', [TenantOutletController::class, 'update'])
                ->where('outlet', '[0-9a-hjkmnp-tv-z]{26}')
                ->name('outlets.update')
                ->block();
            Route::post('outlets/{outlet}/disable', [TenantOutletController::class, 'disable'])
                ->where('outlet', '[0-9a-hjkmnp-tv-z]{26}')
                ->name('outlets.disable')
                ->block();
            Route::post('outlets/{outlet}/users', [TenantOutletController::class, 'assign'])
                ->where('outlet', '[0-9a-hjkmnp-tv-z]{26}')
                ->name('outlets.users.assign')
                ->block();
            Route::delete('outlets/{outlet}/users/{user}', [TenantOutletController::class, 'remove'])
                ->where([
                    'outlet' => '[0-9a-hjkmnp-tv-z]{26}',
                    'user' => '[0-9a-hjkmnp-tv-z]{26}',
                ])
                ->name('outlets.users.remove')
                ->block();
        });
    });
