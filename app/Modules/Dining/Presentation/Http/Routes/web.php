<?php

declare(strict_types=1);

use App\Modules\Dining\Presentation\Http\Web\Controllers\TenantDiningController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/tenants/{tenant}/dining')
    ->name('tenant.dining.')
    ->where(['tenant' => '[0-9a-hjkmnp-tv-z]{26}'])
    ->middleware(['web', 'tenant.authenticated', 'tenant.context', 'tenant.password-current', 'tenant.owner'])
    ->group(function (): void {
        Route::get('/', [TenantDiningController::class, 'index'])->name('index');
        Route::post('floors', [TenantDiningController::class, 'storeFloor'])
            ->name('floors.store')
            ->block();
        Route::put('floors/{floor}', [TenantDiningController::class, 'updateFloor'])
            ->where('floor', '[0-9a-hjkmnp-tv-z]{26}')
            ->name('floors.update')
            ->block();
        Route::post('floors/{floor}/status', [TenantDiningController::class, 'changeFloorStatus'])
            ->where('floor', '[0-9a-hjkmnp-tv-z]{26}')
            ->name('floors.status')
            ->block();
        Route::post('tables', [TenantDiningController::class, 'storeTable'])
            ->name('tables.store')
            ->block();
        Route::put('tables/{table}', [TenantDiningController::class, 'updateTable'])
            ->where('table', '[0-9a-hjkmnp-tv-z]{26}')
            ->name('tables.update')
            ->block();
        Route::post('tables/{table}/status', [TenantDiningController::class, 'changeTableStatus'])
            ->where('table', '[0-9a-hjkmnp-tv-z]{26}')
            ->name('tables.status')
            ->block();
    });
