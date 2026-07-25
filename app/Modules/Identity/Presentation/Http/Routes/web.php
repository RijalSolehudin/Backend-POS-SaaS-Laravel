<?php

declare(strict_types=1);

use App\Modules\Identity\Presentation\Http\Controllers\TenantLoginController;
use App\Modules\Identity\Presentation\Http\Controllers\TenantLogoutController;
use App\Modules\Identity\Presentation\Http\Controllers\TenantPasswordController;
use App\Modules\Identity\Presentation\Http\Controllers\TenantPasswordRecoveryController;
use App\Modules\Identity\Presentation\Http\Controllers\TenantSessionStatusController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')
    ->name('tenant.')
    ->middleware('web')
    ->group(function (): void {
        Route::get('login', [TenantLoginController::class, 'create'])->name('login');
        Route::post('login', [TenantLoginController::class, 'store'])->name('login.store')->block();
        Route::get('forgot-password', [TenantPasswordRecoveryController::class, 'requestForm'])
            ->name('password.request');
        Route::post('forgot-password', [TenantPasswordRecoveryController::class, 'send'])
            ->name('password.email')
            ->block();
        Route::get('reset-password/{token}', [TenantPasswordRecoveryController::class, 'resetForm'])
            ->name('password.reset');
        Route::post('reset-password', [TenantPasswordRecoveryController::class, 'reset'])
            ->name('password.reset.store')
            ->block();

        Route::prefix('tenants/{tenant}')
            ->where(['tenant' => '[0-9a-hjkmnp-tv-z]{26}'])
            ->middleware(['tenant.authenticated', 'tenant.context'])
            ->group(function (): void {
                Route::get('password/change', [TenantPasswordController::class, 'edit'])
                    ->name('password.change');
                Route::put('password', [TenantPasswordController::class, 'update'])
                    ->name('password.update')
                    ->block();
                Route::post('logout', TenantLogoutController::class)->name('logout')->block();
                $statusRoute = Route::get('session/status', TenantSessionStatusController::class)
                    ->name('session.status');
                $statusRoute->setAction([
                    ...$statusRoute->getAction(),
                    'tenant_passive' => true,
                ]);
            });
    });
