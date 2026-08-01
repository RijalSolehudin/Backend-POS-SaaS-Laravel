<?php

declare(strict_types=1);

use App\Modules\Recipe\Presentation\Http\Web\Controllers\TenantRecipeController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/tenants/{tenant}/recipes')
    ->name('tenant.recipes.')
    ->where(['tenant' => '[0-9a-hjkmnp-tv-z]{26}'])
    ->middleware(['web', 'tenant.authenticated', 'tenant.context', 'tenant.password-current', 'tenant.owner'])
    ->group(function (): void {
        Route::get('/', [TenantRecipeController::class, 'index'])->name('index');
        Route::post('/', [TenantRecipeController::class, 'store'])->name('store')->block();
        Route::put('{recipe}', [TenantRecipeController::class, 'update'])
            ->where('recipe', '[0-9a-hjkmnp-tv-z]{26}')
            ->name('update')
            ->block();
        Route::post('{recipe}/status', [TenantRecipeController::class, 'status'])
            ->where('recipe', '[0-9a-hjkmnp-tv-z]{26}')
            ->name('status')
            ->block();
    });
