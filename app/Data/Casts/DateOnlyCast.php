<?php

declare(strict_types=1);

namespace App\Data\Casts;

use Carbon\Carbon;
use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Support\Creation\CreationContext;
use Spatie\LaravelData\Support\DataProperty;

class DateOnlyCast implements Cast
{
    public function cast(DataProperty $property, mixed $value, array $properties, CreationContext $context): ?Carbon
    {
        return match (true) {
            $value === null => null,
            $value instanceof Carbon => $value->startOfDay(),
            default => Carbon::parse($value)->startOfDay(),
        };
    }
}
