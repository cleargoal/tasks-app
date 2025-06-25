<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\TaskSortFieldEnum;
use Spatie\LaravelData\Data;

class TaskSortingData extends Data
{
    /**
     * @param  array<array{field: TaskSortFieldEnum, direction: string}>  $sorts
     */
    public function __construct(
        public array $sorts = []
    ) {}

    public static function fromString(?string $sortString): self
    {
        if (empty($sortString)) {
            return new self;
        }

        $sorts = [];
        $sortParts = explode(',', $sortString);

        foreach ($sortParts as $part) {
            [$field, $direction] = array_pad(explode(':', $part), 2, 'asc');

            if (! in_array($field, TaskSortFieldEnum::values())) {
                continue;
            }

            if (! in_array(strtolower($direction), ['asc', 'desc'])) {
                $direction = 'asc';
            }

            $sorts[] = [
                'field' => TaskSortFieldEnum::from($field),
                'direction' => strtolower($direction),
            ];
        }

        return new self($sorts);
    }
}
