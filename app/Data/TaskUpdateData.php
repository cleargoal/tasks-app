<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\PriorityEnum;
use App\Enums\StatusEnum;
use Illuminate\Validation\Rules\Enum;
use Spatie\LaravelData\Data;

class TaskUpdateData extends Data
{
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?StatusEnum $status = null,
        public ?PriorityEnum $priority = null,
        public ?int $parentId = null,
        public ?\DateTimeInterface $due_date = null,
        public ?\DateTimeInterface $completed_at = null,
    ) {}

    public static function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', new Enum(StatusEnum::class)],
            'priority' => ['nullable', new Enum(PriorityEnum::class)],
            'parentId' => ['nullable', 'exists:tasks,id'],
            'due_date' => ['nullable', 'date'],
            'completed_at' => ['nullable', 'date', 'before_or_equal:now'],
        ];
    }
}
