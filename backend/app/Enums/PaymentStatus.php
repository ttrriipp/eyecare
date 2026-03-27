<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Unpaid = 'unpaid';
    case Paid = 'paid';
    case Refunded = 'refunded';
    case Voided = 'voided';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Unpaid',
            self::Paid => 'Paid',
            self::Refunded => 'Refunded',
            self::Voided => 'Voided',
        };
    }

    /**
     * Valid state transitions for the payment lifecycle.
     */
    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Unpaid => in_array($target, [self::Paid, self::Voided]),
            self::Paid => $target === self::Refunded,
            self::Refunded, self::Voided => false,
        };
    }
}
