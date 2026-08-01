<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Presentation\Console\Commands;

use App\Modules\Inventory\Application\Actions\CheckInventoryRecovery;
use App\Modules\Inventory\Application\Data\InventoryRecoveryDiscrepancy;
use Illuminate\Console\Command;

final class InventoryRecoveryCheckCommand extends Command
{
    protected $signature = 'inventory:recovery-check
        {--tenant= : Limit check to a tenant ULID}
        {--outlet= : Limit check to an outlet ULID}
        {--item= : Limit check to an inventory item ULID}';

    protected $description = 'Read-only check that compares inventory balance projections with ledger replay.';

    public function handle(CheckInventoryRecovery $check): int
    {
        $discrepancies = $check->handle(
            tenantId: $this->optionString('tenant'),
            outletId: $this->optionString('outlet'),
            itemId: $this->optionString('item'),
        );

        if ($discrepancies === []) {
            $this->info('Inventory recovery check passed. No discrepancies found.');

            return self::SUCCESS;
        }

        $this->error('Inventory recovery check found discrepancies.');
        $this->table(
            ['Tenant', 'Outlet', 'Item', 'Expected Qty', 'Actual Qty', 'Expected Cost', 'Actual Cost', 'In Transit'],
            array_map(fn (InventoryRecoveryDiscrepancy $row): array => [
                $row->tenantId,
                $row->outletId,
                $row->itemId,
                $row->expectedQuantity,
                $row->actualQuantity,
                $row->expectedTotalCostMinor,
                $row->actualTotalCostMinor,
                $row->inTransitQuantity,
            ], $discrepancies),
        );

        return self::FAILURE;
    }

    private function optionString(string $key): ?string
    {
        $value = $this->option($key);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
