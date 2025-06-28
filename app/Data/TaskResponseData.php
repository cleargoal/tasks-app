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
        public ?int $parentId,
        #[WithCast(DateOnlyCast::class)]
        #[WithTransformer(DateOnlyTransformer::class)]
        public ?Carbon $dueDate,
        #[WithCast(DateOnlyCast::class)]
        #[WithTransformer(DateOnlyTransformer::class)]
        public ?Carbon $completedAt,
        public Carbon $createdAt,
        public Carbon $updatedAt,
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
            parentId: $task->parent_id,
            dueDate: $task->due_date,
            completedAt: $task->completed_at,
            createdAt: $task->created_at,
            updatedAt: $task->updated_at,
        );
    }

    public function toArray(): array
    {
        $data = parent::toArray();

        $data['due_date'] = $this->dueDate?->format('Y-m-d');

        $data['completed_at'] = $this->completedAt?->format('Y-m-d');

        return $data;
    }
}
