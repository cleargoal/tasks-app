<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\PriorityEnum;
use App\Enums\StatusEnum;
use Spatie\LaravelData\Data;

class TaskFiltersData extends Data
{
    public function __construct(
        public ?PriorityEnum $priority,
        public ?StatusEnum $status,
        public ?string $title,
        public ?string $description,
    ) {}
}
