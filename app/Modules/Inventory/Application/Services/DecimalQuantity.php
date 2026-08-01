<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Application\Services;

final readonly class DecimalQuantity
{
    private const SCALE = 1000;

    public function normalize(string $quantity): string
    {
        return $this->format($this->toScaled($quantity));
    }

    public function toScaled(string $quantity): int
    {
        $normalized = trim($quantity);
        $negative = str_starts_with($normalized, '-');

        if ($negative) {
            $normalized = substr($normalized, 1);
        }

        if (! str_contains($normalized, '.')) {
            $whole = $normalized;
            $decimal = '';
        } else {
            [$whole, $decimal] = explode('.', $normalized, 2);
        }

        $whole = $whole === '' ? '0' : $whole;
        $decimal = str_pad(substr($decimal, 0, 3), 3, '0');
        $scaled = ((int) $whole * self::SCALE) + (int) $decimal;

        return $negative ? -$scaled : $scaled;
    }

    public function format(int $scaled): string
    {
        $negative = $scaled < 0;
        $absolute = abs($scaled);
        $whole = intdiv($absolute, self::SCALE);
        $decimal = $absolute % self::SCALE;

        return ($negative ? '-' : '').$whole.'.'.str_pad((string) $decimal, 3, '0', STR_PAD_LEFT);
    }

    public function unitCostMinor(int $totalCostMinor, int $quantityScaled): ?int
    {
        if ($quantityScaled === 0) {
            return null;
        }

        return intdiv(($totalCostMinor * self::SCALE) + intdiv(abs($quantityScaled), 2), abs($quantityScaled));
    }
}
