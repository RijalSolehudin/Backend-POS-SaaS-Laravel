<?php

declare(strict_types=1);

namespace App\Modules\Sales\Application\Services;

final readonly class QuantityCalculator
{
    public function lineSubtotalMinor(int $unitPriceMinor, string $quantity): int
    {
        $thousandths = $this->toThousandths($quantity);

        return intdiv(($unitPriceMinor * $thousandths) + 500, 1000);
    }

    private function toThousandths(string $quantity): int
    {
        $normalized = trim($quantity);
        $parts = explode('.', $normalized, 2);
        $whole = (int) $parts[0];
        $fraction = str_pad(substr($parts[1] ?? '', 0, 3), 3, '0');

        return ($whole * 1000) + (int) $fraction;
    }
}
