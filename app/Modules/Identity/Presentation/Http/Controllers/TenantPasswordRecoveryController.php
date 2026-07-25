<?php

declare(strict_types=1);

namespace App\Modules\Identity\Presentation\Http\Controllers;

use App\Modules\Identity\Application\Actions\ResetTenantCredential;
use App\Modules\Identity\Domain\Models\User;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

final class TenantPasswordRecoveryController extends Controller
{
    public function requestForm(): View
    {
        return view('identity::forgot-password');
    }

    public function send(Request $request): RedirectResponse
    {
        $input = $request->validate(['email' => ['required', 'email:rfc', 'max:254']]);
        $email = mb_strtolower(trim((string) $input['email']));
        $user = User::query()->where('email', $email)->first();

        if ($user instanceof User && $user->isActive()) {
            Password::broker('users')->sendResetLink(['email' => $email]);
        }

        return back()->with('status', 'If the account is eligible, a password reset link has been sent.');
    }

    public function resetForm(Request $request, string $token): View
    {
        return view('identity::reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function reset(Request $request, ResetTenantCredential $reset): RedirectResponse
    {
        /** @var array{email: string, password: string, password_confirmation: string, token: string} $input */
        $input = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email:rfc', 'max:254'],
            'password' => ['required', 'string', 'confirmed', 'max:128'],
        ]);
        $input['email'] = mb_strtolower(trim($input['email']));
        $broker = Password::broker('users');
        $status = $reset->handle($broker, $input);

        if ($status !== PasswordBroker::PASSWORD_RESET) {
            return back()->withErrors(['email' => [__($status)]])->withInput($request->only('email'));
        }

        return redirect()->route('tenant.login')
            ->with('status', 'Your password has been reset. Sign in again on every device.');
    }
}
