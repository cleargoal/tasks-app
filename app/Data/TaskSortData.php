<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\TaskSortFieldEnum;
use Spatie\LaravelData\Data;

class TaskSortData extends Data
{
    public function __construct(
        public TaskSortFieldEnum $field,
        public string $direction, // 'asc' or 'desc'
    ) {}
}
