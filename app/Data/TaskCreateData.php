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
use Spatie\LaravelData\Data;

/**
 * Data Transfer Object for task creation data.
 *
 * This class defines the structure and validation rules for creating a new task.
 * It is used by the CreateTaskRequest to transform request data into a structured object
 * that can be passed to the service layer.
 */
class TaskCreateData extends Data
{
    /**
     * Create a new TaskCreateData instance.
     *
     * @param string $title The title of the task (required)
     * @param string $description The detailed description of the task (defaults to empty string)
     * @param StatusEnum|null $status The status of the task (defaults to TODO)
     * @param PriorityEnum|null $priority The priority level of the task (defaults to LOW)
     * @param int|null $parentId The ID of the parent task, if this is a subtask
     * @param Carbon|null $dueDate The due date of the task
     */
    public function __construct(
        public string $title,
        public string $description = '',
        public ?StatusEnum $status = StatusEnum::TODO,
        public ?PriorityEnum $priority = PriorityEnum::LOW,
        public ?int $parentId = null,
        #[WithCast(DateOnlyCast::class)]
        #[WithTransformer(DateOnlyTransformer::class)]
        public ?Carbon $dueDate = null,
    ) {
    }

    /**
     * Get the validation rules for task creation.
     *
     * @return array The validation rules
     */
    public static function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', new Enum(StatusEnum::class)],
            'priority' => ['nullable', new Enum(PriorityEnum::class)],
            'parentId' => ['nullable', 'exists:tasks,id'],
            'dueDate' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }
}
