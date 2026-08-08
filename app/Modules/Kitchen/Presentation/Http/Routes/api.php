<?php

declare(strict_types=1);

use App\Modules\Kitchen\Presentation\Http\Api\Controllers\KdsSnapshotController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')
    ->name('api.v1.')
    ->middleware('api')
    ->group(function (): void {
        Route::middleware(['auth:sanctum', 'api.tenant-user-active'])->group(function (): void {
            Route::get('pos/outlets/{outlet}/kds/snapshot', KdsSnapshotController::class)
                ->where('outlet', '[0-9a-hjkmnp-tv-z]{26}')
                ->name('pos.outlets.kds.snapshot');
        });
    });
