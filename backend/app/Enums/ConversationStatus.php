<?php

namespace App\Enums;

enum ConversationStatus: string
{
    case Open   = 'open';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open   => 'Open',
            self::Closed => 'Closed',
        };
    }

    public function isOpen(): bool
    {
        return $this === self::Open;
    }
}
