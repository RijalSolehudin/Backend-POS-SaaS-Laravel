<?php

declare(strict_types=1);

namespace App\Modules\OrderingChannel\Application\Actions;

use App\Modules\Dining\Application\Exceptions\DiningException;
use App\Modules\Dining\Domain\Models\DiningTable;
use App\Modules\OrderingChannel\Domain\Enums\QrSessionStatus;
use App\Modules\OrderingChannel\Domain\Models\OrderingQrSession;
use App\Modules\Tenancy\Application\Contracts\TenantCatalogReference;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

final readonly class CreateQrSession
{
    public function __construct(
        private TenantPermissionGuard $permissions,
        private TenantCatalogReference $tenancy,
    ) {}

    /**
     * @return array{session: OrderingQrSession, token: string}
     */
    public function handle(
        TenantRequestContext $context,
        string $outletId,
        ?string $tableId = null,
        string $contextType = 'table',
        ?CarbonImmutable $expiresAt = null,
    ): array {
        $this->permissions->authorizeManageOutlets($context);

        if (! $this->tenancy->activeOutletExists($context->tenantId, $outletId)) {
            throw DiningException::outletNotFound();
        }

        if ($tableId !== null) {
            $tableExists = DiningTable::query()
                ->where('tenant_id', $context->tenantId)
                ->where('outlet_id', $outletId)
                ->whereKey($tableId)
                ->exists();

            if (! $tableExists) {
                throw DiningException::tableNotFound();
            }
        }

        $raw = Str::random(48);
        $signature = $this->signature($raw);
        $token = $raw.'.'.$signature;
        $session = OrderingQrSession::query()->create([
            'tenant_id' => $context->tenantId,
            'outlet_id' => $outletId,
            'table_id' => $tableId,
            'context_type' => $contextType,
            'token_hash' => hash('sha256', $raw),
            'status' => QrSessionStatus::Active,
            'expires_at' => $expiresAt ?? CarbonImmutable::now()->addMinutes((int) config('ordering.qr_session_ttl_minutes', 1440)),
        ]);

        return ['session' => $session, 'token' => $token];
    }

    private function signature(string $raw): string
    {
        return hash_hmac('sha256', $raw, (string) config('app.key'));
    }
}
