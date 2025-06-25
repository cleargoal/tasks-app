<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class TaskIndexData extends Data
{
    public function __construct(
        public ?TaskFiltersData              $filters = null,
        public TaskSortingData|Optional|null $sort = null,
    ) {}

    protected static function headers(): array
    {
        return [
            'sort' => fn(?string $value) => TaskSortingData::fromString($value),
        ];
    }
}
