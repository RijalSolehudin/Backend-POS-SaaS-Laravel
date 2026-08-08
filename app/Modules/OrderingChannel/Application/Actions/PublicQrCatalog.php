<?php

declare(strict_types=1);

namespace App\Modules\OrderingChannel\Application\Actions;

use App\Modules\Catalog\Application\Actions\ListAvailableOutletCatalog;
use App\Modules\Catalog\Application\Data\AvailableCatalogProduct;
use App\Modules\Tenancy\Application\Data\PosOutletContext;

final readonly class PublicQrCatalog
{
    public function __construct(
        private ResolveQrSession $sessions,
        private ListAvailableOutletCatalog $catalog,
    ) {}

    /**
     * @return array{session: array<string, string|null>, catalog: list<AvailableCatalogProduct>}
     */
    public function handle(string $token): array
    {
        $session = $this->sessions->handle($token);

        return [
            'session' => [
                'id' => $session->id,
                'tenant_id' => $session->tenant_id,
                'outlet_id' => $session->outlet_id,
                'table_id' => $session->table_id,
                'context_type' => $session->context_type,
                'expires_at' => $session->expires_at->toJSON(),
            ],
            'catalog' => $this->catalog->handle(new PosOutletContext($session->tenant_id, $session->outlet_id, 'public-qr', 'public-qr')),
        ];
    }
}
