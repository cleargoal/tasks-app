<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\PriorityEnum;
use App\Enums\StatusEnum;
use Carbon\Carbon;
use Illuminate\Validation\Rules\Enum as EnumRule;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
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
        public ?\DateTimeInterface $due_date = null,
    ) {}

    public static function rules(): array
    {
        return [
            'userId' => ['required', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', new EnumRule(StatusEnum::class)],
            'priority' => ['nullable', new EnumRule(PriorityEnum::class)],
            'parentId' => ['nullable', 'exists:tasks,id'],
            'due_date' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }
}
