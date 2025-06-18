<?php

declare(strict_types = 1);

namespace App\Enums;

enum PriorityEnum: int
{
    case HIGH = 1;
    case MIDHIGH = 2;
    case MID = 3;
    case MIDLOW = 4;
    case LOW = 5;

    public function label(): string
    {
        return match ($this) {
            self::HIGH => 'High',
            self::MIDHIGH => 'Mid-High',
            self::MID => 'Medium',
            self::MIDLOW => 'Mid-Low',
            self::LOW => 'Low',
        };
    }
}
