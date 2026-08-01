<?php

declare(strict_types=1);

use App\Modules\Sales\Presentation\Http\Api\Controllers\OrderController;
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
            Route::get('pos/outlets/{outlet}/shifts/{shift}/summary', [ShiftController::class, 'summary'])
                ->where([
                    'outlet' => '[0-9a-hjkmnp-tv-z]{26}',
                    'shift' => '[0-9a-hjkmnp-tv-z]{26}',
                ])
                ->name('pos.outlets.shifts.summary');
            Route::post('pos/outlets/{outlet}/orders', [OrderController::class, 'store'])
                ->where('outlet', '[0-9a-hjkmnp-tv-z]{26}')
                ->name('pos.outlets.orders.store');
            Route::get('pos/outlets/{outlet}/orders/{order}', [OrderController::class, 'show'])
                ->where([
                    'outlet' => '[0-9a-hjkmnp-tv-z]{26}',
                    'order' => '[0-9a-hjkmnp-tv-z]{26}',
                ])
                ->name('pos.outlets.orders.show');
            Route::post('pos/outlets/{outlet}/orders/{order}/items', [OrderController::class, 'addItem'])
                ->where([
                    'outlet' => '[0-9a-hjkmnp-tv-z]{26}',
                    'order' => '[0-9a-hjkmnp-tv-z]{26}',
                ])
                ->name('pos.outlets.orders.items.store');
            Route::put('pos/outlets/{outlet}/orders/{order}/items/{item}', [OrderController::class, 'updateItem'])
                ->where([
                    'outlet' => '[0-9a-hjkmnp-tv-z]{26}',
                    'order' => '[0-9a-hjkmnp-tv-z]{26}',
                    'item' => '[0-9a-hjkmnp-tv-z]{26}',
                ])
                ->name('pos.outlets.orders.items.update');
            Route::delete('pos/outlets/{outlet}/orders/{order}/items/{item}', [OrderController::class, 'removeItem'])
                ->where([
                    'outlet' => '[0-9a-hjkmnp-tv-z]{26}',
                    'order' => '[0-9a-hjkmnp-tv-z]{26}',
                    'item' => '[0-9a-hjkmnp-tv-z]{26}',
                ])
                ->name('pos.outlets.orders.items.destroy');
            Route::post('pos/outlets/{outlet}/orders/{order}/complete', [OrderController::class, 'complete'])
                ->where([
                    'outlet' => '[0-9a-hjkmnp-tv-z]{26}',
                    'order' => '[0-9a-hjkmnp-tv-z]{26}',
                ])
                ->name('pos.outlets.orders.complete');
            Route::post('pos/outlets/{outlet}/orders/{order}/cancel', [OrderController::class, 'cancel'])
                ->where([
                    'outlet' => '[0-9a-hjkmnp-tv-z]{26}',
                    'order' => '[0-9a-hjkmnp-tv-z]{26}',
                ])
                ->name('pos.outlets.orders.cancel');
            Route::post('pos/outlets/{outlet}/orders/{order}/void', [OrderController::class, 'voidOrder'])
                ->where([
                    'outlet' => '[0-9a-hjkmnp-tv-z]{26}',
                    'order' => '[0-9a-hjkmnp-tv-z]{26}',
                ])
                ->name('pos.outlets.orders.void');
            Route::get('pos/outlets/{outlet}/orders/{order}/receipt', [OrderController::class, 'receipt'])
                ->where([
                    'outlet' => '[0-9a-hjkmnp-tv-z]{26}',
                    'order' => '[0-9a-hjkmnp-tv-z]{26}',
                ])
                ->name('pos.outlets.orders.receipt');
        });
    });
