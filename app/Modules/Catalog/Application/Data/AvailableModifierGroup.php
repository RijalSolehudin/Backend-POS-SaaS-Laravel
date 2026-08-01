<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Data;

final readonly class AvailableModifierGroup
{
    public function __construct(
        public string $id,
        public string $name,
        public bool $required,
        public int $minSelection,
        public int $maxSelection,
        /** @var list<AvailableModifierOption> */
        public array $options,
    ) {}
}
