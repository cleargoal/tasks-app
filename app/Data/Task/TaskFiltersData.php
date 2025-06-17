<?php

declare(strict_types=1);

namespace App\Data\Task;

use Spatie\LaravelData\Data;
use App\Enums\PriorityEnum;
use App\Enums\StatusEnum;

class TaskFiltersData extends Data
{
    public function __construct(
        public ?PriorityEnum $priority,
        public ?StatusEnum $status,
        public ?string $title,
        public ?string $description,
    ) {}
}
