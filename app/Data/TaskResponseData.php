<?php

declare(strict_types=1);

namespace App\Data;

use App\Data\Casts\DateOnlyCast;
use App\Data\Transformers\DateOnlyTransformer;
use App\Enums\PriorityEnum;
use App\Enums\StatusEnum;
use App\Models\Task;
use Carbon\Carbon;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Attributes\WithTransformer;
use Spatie\LaravelData\Data;

class TaskResponseData extends Data
{
    public function __construct(
        public int $id,
        public string $title,
        public string $description,
        public StatusEnum $status,
        public PriorityEnum $priority,
        public ?int $parent_id,
        #[WithCast(DateOnlyCast::class)]
        #[WithTransformer(DateOnlyTransformer::class)]
        public ?Carbon $due_date,
        #[WithCast(DateOnlyCast::class)]
        #[WithTransformer(DateOnlyTransformer::class)]
        public ?Carbon $completed_at,
        public Carbon $created_at,
        public Carbon $updated_at,
    ) {
    }

    public static function fromModel(Task $task): self
    {
        return new self(
            id: $task->id,
            title: $task->title,
            description: $task->description,
            status: $task->status,
            priority: $task->priority,
            parent_id: $task->parent_id,
            due_date: $task->due_date,
            completed_at: $task->completed_at,
            created_at: $task->created_at,
            updated_at: $task->updated_at,
        );
    }
}
