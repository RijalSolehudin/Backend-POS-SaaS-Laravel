<?php

declare(strict_types=1);

use App\Modules\Procurement\Presentation\Http\Web\Controllers\TenantProcurementController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/tenants/{tenant}/procurement')
    ->name('tenant.procurement.')
    ->where(['tenant' => '[0-9a-hjkmnp-tv-z]{26}'])
    ->middleware(['web', 'tenant.authenticated', 'tenant.context', 'tenant.password-current', 'tenant.owner'])
    ->group(function (): void {
        Route::get('/', [TenantProcurementController::class, 'index'])->name('index');
        Route::post('suppliers', [TenantProcurementController::class, 'storeSupplier'])->name('suppliers.store')->block();
        Route::put('suppliers/{supplier}', [TenantProcurementController::class, 'updateSupplier'])
            ->where('supplier', '[0-9a-hjkmnp-tv-z]{26}')
            ->name('suppliers.update')
            ->block();
        Route::post('suppliers/{supplier}/status', [TenantProcurementController::class, 'supplierStatus'])
            ->where('supplier', '[0-9a-hjkmnp-tv-z]{26}')
            ->name('suppliers.status')
            ->block();
        Route::post('supplier-items', [TenantProcurementController::class, 'storeSupplierItem'])->name('supplier-items.store')->block();
    });
