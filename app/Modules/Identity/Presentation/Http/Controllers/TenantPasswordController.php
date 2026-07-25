<?php

declare(strict_types=1);

namespace App\Modules\Identity\Presentation\Http\Controllers;

use App\Modules\Identity\Application\Actions\ChangeTenantPassword;
use App\Modules\Identity\Domain\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class TenantPasswordController extends Controller
{
    public function edit(string $tenant): View
    {
        return view('identity::change-password', ['tenant' => $tenant]);
    }

    public function update(
        Request $request,
        string $tenant,
        ChangeTenantPassword $changePassword,
    ): RedirectResponse {
        $input = $request->validate([
            'current_password' => ['required', 'string', 'max:128'],
            'password' => ['required', 'string', 'confirmed', 'max:128'],
        ]);
        $user = $request->user('web');
        abort_unless($user instanceof User, 401);

        $changePassword->handle(
            $user,
            (string) $input['current_password'],
            (string) $input['password'],
        );

        $request->session()->migrate(true);
        $request->session()->put([
            'tenant.authenticated_at' => now()->getTimestamp(),
            'tenant.last_activity_at' => now()->getTimestamp(),
        ]);

        return redirect()->route('tenant.home', ['tenant' => $tenant])
            ->with('status', 'Your password has been changed. Other sessions and tokens were revoked.');
    }
}
