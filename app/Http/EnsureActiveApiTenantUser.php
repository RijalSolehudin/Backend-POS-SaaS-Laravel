<?php

declare(strict_types=1);

namespace App\Http;

use App\Modules\Identity\Domain\Models\User;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsureActiveApiTenantUser
{
    /**
     * @param  Closure(Request): Response  $next
     *
     * @throws AuthenticationException
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && ! $user->isActive()) {
            throw new AuthenticationException('The authenticated tenant user is inactive.');
        }

        return $next($request);
    }
}
