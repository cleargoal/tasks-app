<?php

declare(strict_types=1);

namespace App\Data\Task;

use Spatie\LaravelData\Data;
use App\Enums\TaskSortFieldEnum;

class TaskSortData extends Data
{
    public function __construct(
        public TaskSortFieldEnum $field,
        public string $direction, // 'asc' or 'desc'
    ) {}
}
