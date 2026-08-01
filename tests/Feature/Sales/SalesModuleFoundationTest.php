<?php

declare(strict_types=1);

namespace Tests\Feature\Sales;

use App\Modules\Sales\Domain\Enums\OrderStatus;
use App\Modules\Sales\Domain\Enums\PaymentMethod;
use App\Modules\Sales\Domain\Enums\PaymentStatus;
use App\Modules\Sales\Domain\Enums\ShiftStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class SalesModuleFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_core_tables_are_migrated_with_mariadb_ulid_conventions(): void
    {
        foreach ([
            'sales_shifts',
            'sales_order_number_counters',
            'sales_orders',
            'sales_order_items',
            'sales_payments',
            'sales_idempotency_records',
        ] as $table) {
            self::assertTrue(Schema::hasTable($table), "{$table} table should exist.");
        }

        $ulidColumns = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->whereIn('TABLE_NAME', [
                'sales_shifts',
                'sales_order_number_counters',
                'sales_orders',
                'sales_order_items',
                'sales_payments',
                'sales_idempotency_records',
            ])
            ->where(function ($query): void {
                $query->where('COLUMN_NAME', 'id')
                    ->orWhere('COLUMN_NAME', 'tenant_id')
                    ->orWhere('COLUMN_NAME', 'outlet_id')
                    ->orWhere('COLUMN_NAME', 'user_id')
                    ->orWhere('COLUMN_NAME', 'shift_id')
                    ->orWhere('COLUMN_NAME', 'order_id')
                    ->orWhere('COLUMN_NAME', 'product_id')
                    ->orWhere('COLUMN_NAME', 'voided_by')
                    ->orWhere('COLUMN_NAME', 'cancelled_by')
                    ->orWhere('COLUMN_NAME', 'resource_id');
            })
            ->get(['TABLE_NAME', 'COLUMN_NAME', 'COLUMN_TYPE', 'CHARACTER_SET_NAME', 'COLLATION_NAME']);

        self::assertNotEmpty($ulidColumns);

        foreach ($ulidColumns as $column) {
            self::assertSame('char(26)', strtolower((string) $column->COLUMN_TYPE));
            self::assertSame('ascii', strtolower((string) $column->CHARACTER_SET_NAME));
            self::assertSame('ascii_bin', strtolower((string) $column->COLLATION_NAME));
        }
    }

    public function test_sales_lifecycle_enums_match_the_accepted_pos_core_policy(): void
    {
        self::assertSame(['open', 'closed', 'voided'], array_map(
            fn (ShiftStatus $status): string => $status->value,
            ShiftStatus::cases(),
        ));
        self::assertSame(['draft', 'completed', 'cancelled', 'voided'], array_map(
            fn (OrderStatus $status): string => $status->value,
            OrderStatus::cases(),
        ));
        self::assertSame(['cash', 'manual_non_cash'], array_map(
            fn (PaymentMethod $method): string => $method->value,
            PaymentMethod::cases(),
        ));
        self::assertSame(['recorded', 'voided'], array_map(
            fn (PaymentStatus $status): string => $status->value,
            PaymentStatus::cases(),
        ));
    }

    public function test_schema_contains_idempotency_and_order_number_uniqueness_backstops(): void
    {
        $indexes = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->whereIn('TABLE_NAME', [
                'sales_idempotency_records',
                'sales_order_number_counters',
                'sales_orders',
                'sales_shifts',
            ])
            ->pluck('INDEX_NAME')
            ->all();

        self::assertContains('sales_idempotency_scope_unique', $indexes);
        self::assertContains('sales_order_counter_scope_unique', $indexes);
        self::assertContains('sales_order_number_scope_unique', $indexes);
        self::assertContains('sales_shifts_open_shift_key_unique', $indexes);
    }
}
