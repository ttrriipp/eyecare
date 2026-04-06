<?php

namespace App\Enums;

enum FrameMaterial: string
{
    case Acetate = 'acetate';
    case Titanium = 'titanium';
    case TR90 = 'tr90';
    case Metal = 'metal';
    case StainlessSteel = 'stainless_steel';
    case Plastic = 'plastic';
    case Wood = 'wood';

    public function label(): string
    {
        return match ($this) {
            self::Acetate => 'Acetate',
            self::Titanium => 'Titanium',
            self::TR90 => 'TR90',
            self::Metal => 'Metal',
            self::StainlessSteel => 'Stainless Steel',
            self::Plastic => 'Plastic',
            self::Wood => 'Wood',
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
