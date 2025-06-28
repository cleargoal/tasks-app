<?php

declare(strict_types=1);

namespace App\Data;

use App\Data\Casts\DateOnlyCast;
use App\Data\Transformers\DateOnlyTransformer;
use App\Enums\PriorityEnum;
use App\Enums\StatusEnum;
use Carbon\Carbon;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Attributes\WithTransformer;
use Spatie\LaravelData\Data;

class TaskFiltersData extends Data
{
    public function __construct(
        public ?PriorityEnum $priority,
        public ?StatusEnum $status,
        public ?string $title,
        public ?string $description,
        #[WithCast(DateOnlyCast::class)]
        #[WithTransformer(DateOnlyTransformer::class)]
        public ?Carbon $dueDate,
        #[WithCast(DateOnlyCast::class)]
        #[WithTransformer(DateOnlyTransformer::class)]
        public ?Carbon $completedAt,
    ) {
    }

    public static function rules(): array
    {
        return [
            'priority' => ['nullable', 'integer', 'min:1', 'max:5'],
            'status' => ['nullable', 'string', 'in:todo,done'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'dueDate' => ['nullable', 'date_format:Y-m-d'],
            'completedAt' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    public static function messages(): array
    {
        return [
            'dueDate.date_format' => 'The due date filter must be in YYYY-MM-DD format.',
            'completedAt.date_format' => 'The completed date filter must be in YYYY-MM-DD format.',
            'priority.min' => 'Priority filter must be between 1 and 5.',
            'priority.max' => 'Priority filter must be between 1 and 5.',
            'status.in' => 'Status filter must be either "todo" or "done".',
        ];
    }
}
