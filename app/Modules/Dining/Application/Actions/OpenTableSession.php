<?php

declare(strict_types=1);

namespace App\Modules\Dining\Application\Actions;

use App\Modules\Dining\Application\Data\OpenTableSessionInput;
use App\Modules\Dining\Application\Exceptions\DiningException;
use App\Modules\Dining\Domain\Enums\TableSessionStatus;
use App\Modules\Dining\Domain\Enums\TableStatus;
use App\Modules\Dining\Domain\Models\DiningTable;
use App\Modules\Dining\Domain\Models\DiningTableSession;
use App\Modules\Tenancy\Application\Contracts\TenantCatalogReference;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final readonly class OpenTableSession
{
    public function __construct(
        private TenantPermissionGuard $permissions,
        private TenantCatalogReference $tenancy,
    ) {}

    public function handle(TenantRequestContext $context, OpenTableSessionInput $input): DiningTableSession
    {
        $this->permissions->authorizeOperatePos($context, $input->outletId);

        return DB::transaction(function () use ($context, $input): DiningTableSession {
            if (! $this->tenancy->activeOutletExists($context->tenantId, $input->outletId)) {
                throw DiningException::outletNotFound();
            }

            $table = $this->activeTable($context, $input->outletId, $input->tableId);

            if ($this->openSessionExists($context->tenantId, $input->outletId, $table->id)) {
                throw DiningException::tableOccupied();
            }

            return DiningTableSession::query()->create([
                'tenant_id' => $context->tenantId,
                'outlet_id' => $input->outletId,
                'table_id' => $table->id,
                'open_table_key' => $this->openTableKey($context->tenantId, $input->outletId, $table->id),
                'party_size' => $input->partySize,
                'status' => TableSessionStatus::Open,
                'opened_by' => $context->userId,
                'opened_at' => CarbonImmutable::now(),
                'notes' => $input->notes,
            ]);
        });
    }

    private function activeTable(TenantRequestContext $context, string $outletId, string $tableId): DiningTable
    {
        $table = DiningTable::query()
            ->where('tenant_id', $context->tenantId)
            ->where('outlet_id', $outletId)
            ->where('status', TableStatus::Active)
            ->whereKey($tableId)
            ->first();

        if (! $table instanceof DiningTable) {
            throw DiningException::tableNotFound();
        }

        return $table;
    }

    private function openSessionExists(string $tenantId, string $outletId, string $tableId): bool
    {
        return DiningTableSession::query()
            ->where('open_table_key', $this->openTableKey($tenantId, $outletId, $tableId))
            ->exists();
    }

    private function openTableKey(string $tenantId, string $outletId, string $tableId): string
    {
        return "{$tenantId}:{$outletId}:{$tableId}";
    }
}
