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
    }
}
