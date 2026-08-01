<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Presentation\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class ExportCatalogCommand extends Command
{
    protected $signature = 'catalog:export {tenant : Tenant ULID} {--pretty : Pretty-print JSON output}';

    protected $description = 'Export tenant catalog as read-only JSON for operational review.';

    public function handle(): int
    {
        $tenantId = (string) $this->argument('tenant');

        $payload = [
            'tenant_id' => $tenantId,
            'categories' => $this->rows('catalog_categories', $tenantId),
            'products' => $this->rows('catalog_products', $tenantId),
            'product_outlet_availabilities' => $this->rows('catalog_product_outlet_availabilities', $tenantId),
            'variants' => $this->rows('catalog_product_variants', $tenantId),
            'variant_outlet_availabilities' => $this->rows('catalog_variant_outlet_availabilities', $tenantId),
            'modifier_groups' => $this->rows('catalog_modifier_groups', $tenantId),
            'modifier_options' => $this->rows('catalog_modifier_options', $tenantId),
            'modifier_option_outlet_overrides' => $this->rows('catalog_modifier_option_outlet_overrides', $tenantId),
        ];

        $flags = JSON_THROW_ON_ERROR;

        if ($this->option('pretty')) {
            $flags |= JSON_PRETTY_PRINT;
        }

        $this->line((string) json_encode($payload, $flags));

        return self::SUCCESS;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rows(string $table, string $tenantId): array
    {
        $rows = [];

        foreach (DB::table($table)
            ->where('tenant_id', $tenantId)
            ->orderBy('id')
            ->get() as $row) {
            $rows[] = get_object_vars($row);
        }

        return $rows;
    }
}
