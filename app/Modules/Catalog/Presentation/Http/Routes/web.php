<?php

declare(strict_types=1);

use App\Modules\Catalog\Presentation\Http\Web\Controllers\TenantCatalogController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/tenants/{tenant}/catalog')
    ->name('tenant.catalog.')
    ->where(['tenant' => '[0-9a-hjkmnp-tv-z]{26}'])
    ->middleware(['web', 'tenant.authenticated', 'tenant.context', 'tenant.password-current', 'tenant.owner'])
    ->group(function (): void {
        Route::get('/', [TenantCatalogController::class, 'index'])->name('index');
        Route::post('categories', [TenantCatalogController::class, 'storeCategory'])
            ->name('categories.store')
            ->block();
        Route::put('categories/{category}', [TenantCatalogController::class, 'updateCategory'])
            ->where('category', '[0-9a-hjkmnp-tv-z]{26}')
            ->name('categories.update')
            ->block();
        Route::post('categories/{category}/status', [TenantCatalogController::class, 'changeCategoryStatus'])
            ->where('category', '[0-9a-hjkmnp-tv-z]{26}')
            ->name('categories.status')
            ->block();
        Route::post('products', [TenantCatalogController::class, 'storeProduct'])
            ->name('products.store')
            ->block();
        Route::put('products/{product}', [TenantCatalogController::class, 'updateProduct'])
            ->where('product', '[0-9a-hjkmnp-tv-z]{26}')
            ->name('products.update')
            ->block();
        Route::post('products/{product}/status', [TenantCatalogController::class, 'changeProductStatus'])
            ->where('product', '[0-9a-hjkmnp-tv-z]{26}')
            ->name('products.status')
            ->block();
        Route::put('products/{product}/availability', [TenantCatalogController::class, 'setAvailability'])
            ->where('product', '[0-9a-hjkmnp-tv-z]{26}')
            ->name('products.availability')
            ->block();
        Route::post('products/{product}/variants', [TenantCatalogController::class, 'storeVariant'])
            ->where('product', '[0-9a-hjkmnp-tv-z]{26}')
            ->name('products.variants.store')
            ->block();
        Route::put('products/{product}/variants/{variant}', [TenantCatalogController::class, 'updateVariant'])
            ->where(['product' => '[0-9a-hjkmnp-tv-z]{26}', 'variant' => '[0-9a-hjkmnp-tv-z]{26}'])
            ->name('products.variants.update')
            ->block();
        Route::post('products/{product}/variants/{variant}/status', [TenantCatalogController::class, 'changeVariantStatus'])
            ->where(['product' => '[0-9a-hjkmnp-tv-z]{26}', 'variant' => '[0-9a-hjkmnp-tv-z]{26}'])
            ->name('products.variants.status')
            ->block();
    });
