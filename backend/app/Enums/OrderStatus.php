<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case ReadyForPickup = 'ready_for_pickup';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Confirmed => 'Confirmed',
            self::ReadyForPickup => 'Ready for Pickup',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Valid state transitions for the order lifecycle.
     */
    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Pending => in_array($target, [self::Confirmed, self::Cancelled]),
            self::Confirmed => in_array($target, [self::ReadyForPickup, self::Cancelled]),
            self::ReadyForPickup => in_array($target, [self::Completed, self::Cancelled]),
            self::Completed, self::Cancelled => false,
        };
    }
}
