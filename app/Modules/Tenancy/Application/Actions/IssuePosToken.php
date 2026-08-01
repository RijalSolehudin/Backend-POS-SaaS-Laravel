<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\Actions;

use App\Modules\Identity\Application\Contracts\PosTokenIssuer;
use App\Modules\Identity\Application\Contracts\TenantCredentialVerifier;
use App\Modules\Identity\Application\Exceptions\IdentityException;
use App\Modules\Tenancy\Application\Data\IssuedPosSession;
use App\Modules\Tenancy\Application\Data\PosTokenRequest;
use App\Modules\Tenancy\Application\Exceptions\TenancyException;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;
use App\Modules\Tenancy\Domain\Enums\OutletStatus;
use App\Modules\Tenancy\Domain\Models\Outlet;
use Carbon\CarbonImmutable;

final readonly class IssuePosToken
{
    public function __construct(
        private TenantCredentialVerifier $credentials,
        private ResolveRegisteredPosDevice $devices,
        private ResolveTenantRequestContext $tenantContext,
        private PosTokenIssuer $tokens,
        private TenantPermissionGuard $permissions,
    ) {}

    public function handle(PosTokenRequest $request): IssuedPosSession
    {
        $user = $this->credentials->verify($request->email, $request->password);

        if ($user === null) {
            throw IdentityException::invalidCredentials();
        }

        $context = $this->tenantContext->handle($user->userId);

        if ($context === null || $context->tenantId !== $user->tenantId) {
            throw TenancyException::forbidden();
        }

        $device = $this->devices->handle($user->tenantId, $request->installationId);

        if ($device->outlet_id !== $request->outletId) {
            throw TenancyException::outletNotFound();
        }

        $outlet = Outlet::query()
            ->where('tenant_id', $user->tenantId)
            ->whereKey($request->outletId)
            ->first();

        if (! $outlet instanceof Outlet || $outlet->status !== OutletStatus::Active) {
            throw TenancyException::outletNotFound();
        }

        $this->permissions->authorizeOperatePos($context, $request->outletId);

        $expiresAt = CarbonImmutable::now()->addDays(30);
        $issuedToken = $this->tokens->replaceForDevice($user->userId, (string) $device->getKey(), $expiresAt);

        return new IssuedPosSession(
            token: $issuedToken->plainTextToken,
            expiresAt: $issuedToken->expiresAt,
            tenantId: $user->tenantId,
            outletId: $request->outletId,
            deviceId: (string) $device->getKey(),
            userId: $user->userId,
            mustChangePassword: $user->mustChangePassword,
        );
    }
}
