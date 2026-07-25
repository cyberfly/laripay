<?php

namespace App\Enums;

enum ProductType: string
{
    case Physical = 'physical';
    case Digital = 'digital';

    public function label(): string
    {
        return match ($this) {
            self::Physical => 'Physical',
            self::Digital => 'Digital',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Physical => 'blue',
            self::Digital => 'violet',
        };
    }
}
