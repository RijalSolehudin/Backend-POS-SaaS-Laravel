<?php

declare(strict_types=1);

namespace App\Modules\Identity\Presentation\Http\Middleware;

use App\Modules\Identity\Domain\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireCurrentTenantPassword
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('web');

        if (
            $user instanceof User
            && $user->must_change_password
            && ! $request->routeIs('tenant.password.change', 'tenant.password.update', 'tenant.logout')
        ) {
            return redirect()->route('tenant.password.change', [
                'tenant' => (string) $request->route('tenant'),
            ]);
        }

        return $next($request);
    }
}
