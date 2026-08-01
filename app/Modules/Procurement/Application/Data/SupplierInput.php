<?php

declare(strict_types=1);

namespace App\Modules\Procurement\Application\Data;

final readonly class SupplierInput
{
    public function __construct(
        public string $name,
        public string $code,
    ) {}
}
