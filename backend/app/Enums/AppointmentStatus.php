<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Scheduled = 'scheduled';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Scheduled',
            self::Confirmed => 'Confirmed',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    /** Statuses that a customer may transition from (i.e. cancel themselves). */
    public function isCustomerCancellable(): bool
    {
        return match ($this) {
            self::Scheduled, self::Confirmed => true,
            default                          => false,
        };
    }
}
