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

/**
 * Data Transfer Object for task response data.
 *
 * This class is responsible for transforming Task models into a standardized response format,
 * handling date formatting and providing a consistent structure for API responses.
 */
class TaskResponseData extends Data
{
    /**
     * Create a new TaskResponseData instance.
     *
     * @param int $id The unique identifier of the task
     * @param string $title The title of the task
     * @param string $description The detailed description of the task
     * @param StatusEnum $status The current status of the task (e.g., TODO, IN_PROGRESS, DONE)
     * @param PriorityEnum $priority The priority level of the task
     * @param int|null $parentId The ID of the parent task, if this is a subtask
     * @param Carbon|null $dueDate The due date of the task
     * @param Carbon|null $completedAt The date when the task was completed
     * @param Carbon $createdAt The date when the task was created
     * @param Carbon $updatedAt The date when the task was last updated
     */
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

    /**
     * Create a TaskResponseData instance from a Task model.
     *
     * @param Task $task The task model to convert
     * @return self A new TaskResponseData instance with data from the task model
     */
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

    /**
     * Convert the data object to an array.
     *
     * Overrides the parent toArray method to ensure date fields are properly formatted
     * in Y-m-d format for API responses.
     *
     * @return array The data as an associative array with formatted dates
     */
    public function toArray(): array
    {
        $data = parent::toArray();

        $data['due_date'] = $this->dueDate?->format('Y-m-d');

        $data['completed_at'] = $this->completedAt?->format('Y-m-d');

        return $data;
    }
}
