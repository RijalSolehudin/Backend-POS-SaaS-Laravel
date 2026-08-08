<?php

declare(strict_types=1);

use App\Modules\Sync\Presentation\Http\Api\Controllers\SyncController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')
    ->name('api.v1.')
    ->middleware('api')
    ->group(function (): void {
        Route::middleware(['auth:sanctum', 'api.tenant-user-active'])->group(function (): void {
            Route::get('pos/outlets/{outlet}/sync/bootstrap', [SyncController::class, 'bootstrap'])
                ->where('outlet', '[0-9a-hjkmnp-tv-z]{26}')
                ->name('pos.outlets.sync.bootstrap');
            Route::get('pos/outlets/{outlet}/sync/catalog-snapshot', [SyncController::class, 'catalogSnapshot'])
                ->where('outlet', '[0-9a-hjkmnp-tv-z]{26}')
                ->name('pos.outlets.sync.catalog-snapshot');
            Route::post('pos/outlets/{outlet}/sync/push', [SyncController::class, 'push'])
                ->where('outlet', '[0-9a-hjkmnp-tv-z]{26}')
                ->name('pos.outlets.sync.push');
            Route::get('pos/outlets/{outlet}/sync/pull', [SyncController::class, 'pull'])
                ->where('outlet', '[0-9a-hjkmnp-tv-z]{26}')
                ->name('pos.outlets.sync.pull');
        });
    });
