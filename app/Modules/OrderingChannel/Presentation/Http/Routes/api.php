<?php

declare(strict_types=1);

use App\Modules\OrderingChannel\Presentation\Http\Api\Controllers\PublicQrCatalogController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')
    ->name('api.v1.')
    ->middleware('api')
    ->group(function (): void {
        Route::get('qr/{token}', PublicQrCatalogController::class)->name('qr.show');
    });
