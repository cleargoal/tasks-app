<?php

declare(strict_types=1);

namespace App\Data;

use App\Data\Casts\DateOnlyCast;
use App\Data\Transformers\DateOnlyTransformer;
use App\Enums\PriorityEnum;
use App\Enums\StatusEnum;
use Carbon\Carbon;
use Illuminate\Validation\Rules\Enum;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Attributes\WithTransformer;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;

/**
 * Data Transfer Object for task update data.
 *
 * This class defines the structure and validation rules for updating an existing task.
 * It is used by the UpdateTaskRequest to transform request data into a structured object
 * that can be passed to the service layer. All properties are nullable to allow partial updates.
 */
class TaskUpdateData extends Data
{
    /**
     * Create a new TaskUpdateData instance.
     *
     * @param string|null $title The title of the task
     * @param string|null $description The detailed description of the task
     * @param StatusEnum|null $status The status of the task
     * @param PriorityEnum|null $priority The priority level of the task
     * @param int|null $parentId The ID of the parent task, if this is a subtask
     * @param Carbon|null $dueDate The due date of the task
     * @param Carbon|null $completedAt The date when the task was completed
     */
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?StatusEnum $status = null,
        public ?PriorityEnum $priority = null,
        public ?int $parentId = null,
        #[WithCast(DateOnlyCast::class)]
        #[WithTransformer(DateOnlyTransformer::class)]
        public ?Carbon $dueDate = null,
        #[WithCast(DateTimeInterfaceCast::class)]
        public ?Carbon $completedAt = null,
    ) {
    }

    /**
     * Get the validation rules for task updates.
     *
     * @return array The validation rules
     */
    public static function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', new Enum(StatusEnum::class)],
            'priority' => ['nullable', new Enum(PriorityEnum::class)],
            'parentId' => ['nullable', 'exists:tasks,id'],
            'dueDate' => ['nullable', 'date'],
            'completedAt' => ['nullable', 'date', 'before_or_equal:now'],
        ];
    }
}
