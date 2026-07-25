<?php

declare(strict_types=1);

namespace Tests\Feature\Foundation;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class MariaDbCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_backed_tests_run_on_mariadb_in_strict_mode(): void
    {
        self::assertSame('mariadb', config('database.default'));

        $version = DB::scalar('SELECT VERSION()');
        $sqlMode = DB::scalar('SELECT @@SESSION.sql_mode');

        self::assertIsString($version);
        self::assertStringContainsStringIgnoringCase('mariadb', $version);

        self::assertIsString($sqlMode);
        self::assertMatchesRegularExpression(
            '/STRICT_(TRANS_TABLES|ALL_TABLES)/',
            $sqlMode,
        );

        self::assertTrue(Schema::hasTable('migrations'));
        self::assertTrue(Schema::hasTable('users'));
        self::assertTrue(Schema::hasTable('user_role_assignments'));
        self::assertTrue(Schema::hasTable('tenants'));
        self::assertTrue(Schema::hasTable('outlets'));
        self::assertTrue(Schema::hasTable('tenant_memberships'));
        self::assertTrue(Schema::hasTable('tenant_provisioning_requests'));
        self::assertTrue(Schema::hasTable('tenancy_audit_events'));

        $ulidColumns = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->whereIn('TABLE_NAME', [
                'users',
                'user_role_assignments',
                'tenants',
                'outlets',
                'tenant_memberships',
                'tenant_provisioning_requests',
                'tenancy_audit_events',
            ])
            ->where(function ($query): void {
                $query->where('COLUMN_NAME', 'id')
                    ->orWhere('COLUMN_NAME', 'tenant_id')
                    ->orWhere('COLUMN_NAME', 'user_id')
                    ->orWhere('COLUMN_NAME', 'outlet_id')
                    ->orWhere('COLUMN_NAME', 'owner_user_id')
                    ->orWhere('COLUMN_NAME', 'membership_id')
                    ->orWhere('COLUMN_NAME', 'role_assignment_id')
                    ->orWhere('COLUMN_NAME', 'target_tenant_id');
            })
            ->get(['TABLE_NAME', 'COLUMN_NAME', 'COLUMN_TYPE', 'CHARACTER_SET_NAME', 'COLLATION_NAME']);

        self::assertNotEmpty($ulidColumns);

        foreach ($ulidColumns as $column) {
            self::assertSame('char(26)', strtolower((string) $column->COLUMN_TYPE));
            self::assertSame('ascii', strtolower((string) $column->CHARACTER_SET_NAME));
            self::assertSame('ascii_bin', strtolower((string) $column->COLLATION_NAME));
        }
    }
}
