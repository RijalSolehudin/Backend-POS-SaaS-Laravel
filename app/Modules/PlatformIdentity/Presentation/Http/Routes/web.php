<?php

declare(strict_types=1);

use App\Modules\PlatformIdentity\Presentation\Http\Controllers\PlatformHomeController;
use App\Modules\PlatformIdentity\Presentation\Http\Controllers\PlatformLoginController;
use App\Modules\PlatformIdentity\Presentation\Http\Controllers\PlatformLogoutController;
use App\Modules\PlatformIdentity\Presentation\Http\Controllers\PlatformMfaChallengeController;
use App\Modules\PlatformIdentity\Presentation\Http\Controllers\PlatformMfaEnrollmentController;
use App\Modules\PlatformIdentity\Presentation\Http\Controllers\PlatformRecoveryCodeController;
use App\Modules\PlatformIdentity\Presentation\Http\Controllers\PlatformSecurityController;
use App\Modules\PlatformIdentity\Presentation\Http\Controllers\PlatformSensitiveConfirmationController;
use App\Modules\PlatformIdentity\Presentation\Http\Controllers\PlatformSessionReplacementController;
use Illuminate\Support\Facades\Route;

Route::prefix('platform')
    ->name('platform.')
    ->middleware('platform.web')
    ->group(function (): void {
        Route::get('login', [PlatformLoginController::class, 'create'])->name('login');
        Route::post('login', [PlatformLoginController::class, 'store'])->name('login.store')->block();

        Route::get('mfa/challenge', [PlatformMfaChallengeController::class, 'create'])->name('mfa.challenge');
        Route::post('mfa/challenge', [PlatformMfaChallengeController::class, 'store'])->name('mfa.challenge.store')->block();
        Route::get('mfa/enroll', [PlatformMfaEnrollmentController::class, 'create'])->name('mfa.enroll');
        Route::post('mfa/enroll', [PlatformMfaEnrollmentController::class, 'store'])->name('mfa.enroll.store')->block();

        Route::get('session-replacement', [PlatformSessionReplacementController::class, 'create'])
            ->name('session-replacement');
        Route::post('session-replacement', [PlatformSessionReplacementController::class, 'store'])
            ->name('session-replacement.store')
            ->block();

        Route::middleware('platform.authenticated')->group(function (): void {
            Route::get('/', PlatformHomeController::class)->name('home');
            Route::get('security', [PlatformSecurityController::class, 'index'])->name('security');
            Route::post('logout', PlatformLogoutController::class)->name('logout')->block();

            Route::get('confirm-sensitive-action', [PlatformSensitiveConfirmationController::class, 'create'])
                ->name('confirm-sensitive');
            Route::post('confirm-sensitive-action', [PlatformSensitiveConfirmationController::class, 'store'])
                ->name('confirm-sensitive.store')
                ->block();

            Route::delete('sessions/{session}', [PlatformSecurityController::class, 'revoke'])
                ->middleware('platform.confirmed')
                ->where('session', '[A-Za-z0-9]+')
                ->name('sessions.revoke')
                ->block();

            Route::post('recovery-codes/regenerate', [PlatformRecoveryCodeController::class, 'store'])
                ->middleware('platform.confirmed')
                ->name('recovery-codes.regenerate')
                ->block();
        });
    });
