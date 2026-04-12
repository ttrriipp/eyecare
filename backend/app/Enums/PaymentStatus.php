<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Unpaid = 'unpaid';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case PartiallyRefunded = 'partially_refunded';
    case Refunded = 'refunded';
    case Voided = 'voided';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Unpaid',
            self::PartiallyPaid => 'Partially Paid',
            self::Paid => 'Paid',
            self::PartiallyRefunded => 'Partially Refunded',
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
            self::Unpaid => in_array($target, [self::PartiallyPaid, self::Paid, self::Voided], true),
            self::PartiallyPaid => in_array($target, [self::Paid, self::PartiallyRefunded, self::Refunded], true),
            self::Paid => in_array($target, [self::PartiallyRefunded, self::Refunded], true),
            self::PartiallyRefunded => in_array($target, [self::PartiallyRefunded, self::Refunded], true),
            self::Refunded, self::Voided => false,
        };
    }
}
