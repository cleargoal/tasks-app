<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Data\Task\TaskIndexData;
use App\Models\Task;
use Illuminate\Support\Collection;

class TaskRepository
{
    public function getTasks(TaskIndexData $data): Collection
    {
        $query = Task::query();

        if ($filters = $data->filters) {
            if ($filters->priority) {
                $query->where('priority', $filters->priority->value);
            }

            if ($filters->status) {
                $query->where('status', $filters->status->value);
            }

            if ($filters->title) {
                $query->where('title', 'like', '%' . $filters->title . '%');
            }

            if ($filters->description) {
                $query->where('description', 'like', '%' . $filters->description . '%');
            }
        }

        foreach ($data->sort as $sort) {
            $query->orderBy($sort->field->value, $sort->direction);
        }

        return $query->get();
    }
}
