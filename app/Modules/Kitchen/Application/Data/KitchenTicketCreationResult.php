<?php

declare(strict_types=1);

namespace App\Modules\Kitchen\Application\Data;

use App\Modules\Kitchen\Domain\Models\KitchenTicket;

final readonly class KitchenTicketCreationResult
{
    /**
     * @param  list<KitchenTicket>  $tickets
     * @param  list<string>  $missingOrderItemIds
     */
    public function __construct(
        public array $tickets,
        public array $missingOrderItemIds,
    ) {}
}
