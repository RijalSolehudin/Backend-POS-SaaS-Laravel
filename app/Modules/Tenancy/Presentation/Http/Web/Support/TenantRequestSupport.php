<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Presentation\Http\Web\Support;

use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Presentation\Http\Middleware\ResolveTenantContext;
use App\Shared\Application\Context\ActorContext;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class TenantRequestSupport
{
    public function context(Request $request): TenantRequestContext
    {
        $context = $request->attributes->get(ResolveTenantContext::ATTRIBUTE);
        abort_unless($context instanceof TenantRequestContext, 500);

        return $context;
    }

    public function actor(TenantRequestContext $context): ActorContext
    {
        return new ActorContext(
            actorType: 'tenant_user',
            actorId: $context->userId,
            correlationId: strtolower((string) Str::ulid()),
        );
    }
}
