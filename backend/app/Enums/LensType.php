<?php

namespace App\Enums;

enum LensType: string
{
    case SingleVision = 'single_vision';
    case Bifocal = 'bifocal';
    case Progressive = 'progressive';
    case Photochromic = 'photochromic';
    case Daily = 'daily';
    case Monthly = 'monthly';
    case Polarized = 'polarized';

    public function label(): string
    {
        return match ($this) {
            self::SingleVision => 'Single Vision',
            self::Bifocal => 'Bifocal',
            self::Progressive => 'Progressive',
            self::Photochromic => 'Photochromic',
            self::Daily => 'Daily',
            self::Monthly => 'Monthly',
            self::Polarized => 'Polarized',
        };
    }

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
