<?php

namespace App\Enums;

enum DiscountType: string
{
    case Fixed = 'fixed';
    case Percentage = 'percentage';

    public function label(): string
    {
        return match ($this) {
            self::Fixed => 'Fixed amount',
            self::Percentage => 'Percentage',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Fixed => 'blue',
            self::Percentage => 'violet',
        };
    }
}
