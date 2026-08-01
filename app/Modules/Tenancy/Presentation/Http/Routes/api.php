<?php

declare(strict_types=1);

use App\Modules\Tenancy\Presentation\Http\Api\Controllers\PosAuthController;
use App\Modules\Tenancy\Presentation\Http\Api\Controllers\PosOutletContextController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')
    ->name('api.v1.')
    ->middleware('api')
    ->group(function (): void {
        Route::post('pos/auth/login', [PosAuthController::class, 'login'])->name('pos.auth.login');

        Route::middleware(['auth:sanctum', 'api.tenant-user-active'])->group(function (): void {
            Route::post('pos/auth/logout', [PosAuthController::class, 'logout'])->name('pos.auth.logout');
            Route::get('pos/outlets/{outlet}/context', PosOutletContextController::class)
                ->where('outlet', '[0-9a-hjkmnp-tv-z]{26}')
                ->name('pos.outlets.context');
        });
    });
