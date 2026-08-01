<?php

declare(strict_types=1);

use App\Modules\Sales\Presentation\Http\Api\Controllers\ShiftController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')
    ->name('api.v1.')
    ->middleware('api')
    ->group(function (): void {
        Route::middleware(['auth:sanctum', 'api.tenant-user-active'])->group(function (): void {
            Route::get('pos/outlets/{outlet}/shifts/current', [ShiftController::class, 'current'])
                ->where('outlet', '[0-9a-hjkmnp-tv-z]{26}')
                ->name('pos.outlets.shifts.current');
            Route::post('pos/outlets/{outlet}/shifts/open', [ShiftController::class, 'open'])
                ->where('outlet', '[0-9a-hjkmnp-tv-z]{26}')
                ->name('pos.outlets.shifts.open');
            Route::post('pos/outlets/{outlet}/shifts/{shift}/close', [ShiftController::class, 'close'])
                ->where([
                    'outlet' => '[0-9a-hjkmnp-tv-z]{26}',
                    'shift' => '[0-9a-hjkmnp-tv-z]{26}',
                ])
                ->name('pos.outlets.shifts.close');
        });
    });
