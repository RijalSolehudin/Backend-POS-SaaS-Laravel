<?php

declare(strict_types=1);

namespace App\Modules\Identity\Presentation\Http\Controllers;

use App\Modules\Identity\Application\Actions\AuthenticateTenantUser;
use App\Modules\Identity\Application\Services\TenantLoginThrottle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

final class TenantLoginController extends Controller
{
    public function create(): View
    {
        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();
        }

        return view('identity::login');
    }

    public function store(
        Request $request,
        AuthenticateTenantUser $authenticate,
        TenantLoginThrottle $throttle,
    ): RedirectResponse {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:254'],
            'password' => ['required', 'string', 'max:128'],
        ]);
        $email = mb_strtolower(trim((string) $credentials['email']));
        $ip = (string) $request->ip();
        $retryAfter = $throttle->retryAfter($email, $ip);

        if ($retryAfter > 0) {
            return back()->withErrors([
                'email' => ["Too many sign-in attempts. Try again in {$retryAfter} seconds."],
            ])->onlyInput('email');
        }

        $result = $authenticate->handle($email, (string) $credentials['password']);

        if ($result === null) {
            $throttle->recordFailure($email, $ip);

            return back()->withErrors([
                'email' => ['The provided credentials are invalid.'],
            ])->onlyInput('email');
        }

        $throttle->clear($email, $ip);
        Auth::guard('web')->loginUsingId($result->userId);
        $request->session()->regenerate();
        $request->session()->put([
            'tenant.authenticated_at' => now()->getTimestamp(),
            'tenant.last_activity_at' => now()->getTimestamp(),
        ]);

        $route = $result->mustChangePassword ? 'tenant.password.change' : 'tenant.home';

        return redirect()->route($route, ['tenant' => $result->tenantId]);
    }
}
