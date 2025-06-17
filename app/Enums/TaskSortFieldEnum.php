<?php

declare(strict_types=1);

namespace App\Enums;

enum TaskSortFieldEnum: string
{
    case CREATED_AT = 'created_at';
    case TITLE = 'title';
    case PRIORITY = 'priority';
    case DUE_DATE = 'due_date';
    case STATUS = 'status';
    case COMPLETED_AT = 'completed_at';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
