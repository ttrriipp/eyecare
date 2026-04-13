<?php

declare(strict_types=1);

namespace App\Support;

final class Money
{
    /**
     * Format an amount as Philippine pesos (₱) with two decimal places.
     */
    public static function peso(float|int|string|null $amount): string
    {
        return '₱'.number_format((float) ($amount ?? 0), 2);
    }
}
