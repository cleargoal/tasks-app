<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\PriorityEnum;
use App\Enums\StatusEnum;
use Spatie\LaravelData\Data;

class TaskCreateData extends Data
{
    public function __construct(
        public int $userId,
        public string $title,
        public string $description,
        public StatusEnum $status = StatusEnum::TODO,
        public PriorityEnum $priority = PriorityEnum::LOW,
        public ?int $parentId = null,
    ) {}

    public static function rules(): array
    {
        return [
            'userId' => ['required', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'status' => ['nullable', 'in:todo,in_progress,done'], // or use Enum rule
            'priority' => ['nullable', 'in:low,medium,high'],
            'parentId' => ['nullable', 'exists:tasks,id'],
        ];
    }
}
