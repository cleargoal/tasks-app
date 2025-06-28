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

class TaskCreateData extends Data
{
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
