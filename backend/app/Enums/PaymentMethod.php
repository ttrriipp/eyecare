<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case GCash = 'gcash';
    case Maya = 'maya';
    case Card = 'card';
    case BankTransfer = 'bank_transfer';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::GCash => 'GCash',
            self::Maya => 'Maya',
            self::Card => 'Card',
            self::BankTransfer => 'Bank Transfer',
        };
    }

    /**
     * Helper for request validation rules.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $method) => $method->value, self::cases());
    }
}
