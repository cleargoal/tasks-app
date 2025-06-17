<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\PriorityEnum;
use Spatie\LaravelData\Data;

class TaskUpdateData extends Data
{
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?PriorityEnum $priority = null,
        public ?int $parentId = null,
    ) {}

    public static function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['nullable', 'in:low,medium,high'], // or use EnumRule if preferred
            'parentId' => ['nullable', 'exists:tasks,id'],
        ];
    }
}
