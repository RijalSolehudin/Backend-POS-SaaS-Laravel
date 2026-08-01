<?php

declare(strict_types=1);

use App\Modules\Inventory\Presentation\Http\Web\Controllers\TenantInventoryController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/tenants/{tenant}/inventory')
    ->name('tenant.inventory.')
    ->where(['tenant' => '[0-9a-hjkmnp-tv-z]{26}'])
    ->middleware(['web', 'tenant.authenticated', 'tenant.context', 'tenant.password-current', 'tenant.owner'])
    ->group(function (): void {
        Route::get('/', [TenantInventoryController::class, 'index'])->name('index');
        Route::post('units', [TenantInventoryController::class, 'storeUnit'])
            ->name('units.store')
            ->block();
        Route::put('units/{unit}', [TenantInventoryController::class, 'updateUnit'])
            ->where('unit', '[0-9a-hjkmnp-tv-z]{26}')
            ->name('units.update')
            ->block();
        Route::post('units/{unit}/status', [TenantInventoryController::class, 'changeUnitStatus'])
            ->where('unit', '[0-9a-hjkmnp-tv-z]{26}')
            ->name('units.status')
            ->block();
        Route::post('items', [TenantInventoryController::class, 'storeItem'])
            ->name('items.store')
            ->block();
        Route::put('items/{item}', [TenantInventoryController::class, 'updateItem'])
            ->where('item', '[0-9a-hjkmnp-tv-z]{26}')
            ->name('items.update')
            ->block();
        Route::post('items/{item}/status', [TenantInventoryController::class, 'changeItemStatus'])
            ->where('item', '[0-9a-hjkmnp-tv-z]{26}')
            ->name('items.status')
            ->block();
        Route::put('items/{item}/outlet-settings', [TenantInventoryController::class, 'setOutletSettings'])
            ->where('item', '[0-9a-hjkmnp-tv-z]{26}')
            ->name('items.outlet-settings')
            ->block();
        Route::post('items/{item}/opening-balances', [TenantInventoryController::class, 'recordOpeningBalance'])
            ->where('item', '[0-9a-hjkmnp-tv-z]{26}')
            ->name('items.opening-balances.store')
            ->block();
        Route::post('items/{item}/adjustments', [TenantInventoryController::class, 'recordAdjustment'])
            ->where('item', '[0-9a-hjkmnp-tv-z]{26}')
            ->name('items.adjustments.store')
            ->block();
        Route::post('items/{item}/waste', [TenantInventoryController::class, 'recordWaste'])
            ->where('item', '[0-9a-hjkmnp-tv-z]{26}')
            ->name('items.waste.store')
            ->block();
    });
