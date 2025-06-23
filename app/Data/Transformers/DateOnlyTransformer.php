<?php

declare(strict_types=1);

namespace App\Data\Transformers;

use Carbon\Carbon;
use Spatie\LaravelData\Support\DataProperty;
use Spatie\LaravelData\Support\Transformation\TransformationContext;
use Spatie\LaravelData\Transformers\Transformer;

class DateOnlyTransformer implements Transformer
{
    public function transform(DataProperty $property, mixed $value, TransformationContext $context): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->format('Y-m-d');
        }

        return Carbon::parse($value)->format('Y-m-d');
    }
}
