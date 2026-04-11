<?php

namespace App\Enums;

enum FeedbackType: string
{
    case Product = 'product';
    case Appointment = 'appointment';
    case Service = 'service';

    public function label(): string
    {
        return match ($this) {
            self::Product => 'Product',
            self::Appointment => 'Appointment',
            self::Service => 'Service',
        };
    }
}
