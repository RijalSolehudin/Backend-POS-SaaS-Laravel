<?php

declare(strict_types=1);

namespace App\Modules\Sync\Application\Actions;

use App\Modules\Catalog\Application\Actions\ListAvailableOutletCatalog;
use App\Modules\Catalog\Application\Data\AvailableCatalogProduct;
use App\Modules\Tenancy\Application\Data\PosOutletContext;

final readonly class GetOfflineCatalogSnapshot
{
    public function __construct(
        private ListAvailableOutletCatalog $catalog,
        private RecordSyncOutbox $outbox,
    ) {}

    /**
     * @return array{version: string, catalog: list<AvailableCatalogProduct>, retention_hours: int}
     */
    public function handle(PosOutletContext $context): array
    {
        $catalog = $this->catalog->handle($context);
        $version = hash('sha256', json_encode($catalog, JSON_THROW_ON_ERROR));

        $this->outbox->handle(
            tenantId: $context->tenantId,
            outletId: $context->outletId,
            eventType: 'catalog.snapshot.generated',
            resourceType: 'catalog_snapshot',
            resourceId: null,
            payload: ['version' => $version],
        );

        return [
            'version' => $version,
            'catalog' => $catalog,
            'retention_hours' => (int) config('sync.local_cache_retention_hours', 72),
        ];
    }
}
