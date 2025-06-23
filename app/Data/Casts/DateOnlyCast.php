<?php

declare(strict_types=1);

namespace App\Data\Casts;

use Carbon\Carbon;
use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Support\DataProperty;
use Spatie\LaravelData\Support\Creation\CreationContext;

class DateOnlyCast implements Cast
{
    public function cast(DataProperty $property, mixed $value, array $properties, CreationContext $context): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->startOfDay();
        }

        return Carbon::parse($value)->startOfDay();
    }
}
