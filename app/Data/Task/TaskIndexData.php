<?php

declare(strict_types=1);

namespace App\Data\Task;

use Spatie\LaravelData\Data;

class TaskIndexData extends Data
{
    public function __construct(
        public ?TaskFiltersData $filters,
        /** @var TaskSortData[] */
        public array $sort = [],
    ) {}
}
