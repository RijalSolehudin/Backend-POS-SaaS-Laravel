<?php

declare(strict_types=1);

namespace App\Modules\Kitchen\Application\Data;

final readonly class PrinterDispatchResult
{
    private function __construct(
        public bool $sent,
        public ?string $errorMessage = null,
    ) {}

    public static function sent(): self
    {
        return new self(true);
    }

    public static function failed(string $message): self
    {
        return new self(false, $message);
    }
}
