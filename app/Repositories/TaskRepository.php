<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Data\TaskCreateData;
use App\Data\TaskFiltersData;
use App\Data\TaskSortingData;
use App\Data\TaskUpdateData;
use App\Enums\StatusEnum;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TaskRepository
{
    public function getByFiltersAndSort(int $userId, ?TaskFiltersData $filters = null, ?TaskSortingData $sort = null): Collection
    {
        $query = Task::forUser($userId);

        $this->applyFilters($query, $filters);
        $this->applySorting($query, $sort);

        return $query->get();
    }

    public function findById(int $userId, int $id): Task
    {
        return Task::forUser($userId)->findOrFail($id);
    }

    public function findByIdWithLock(int $userId, int $id): Task
    {
        return Task::forUser($userId)->lockForUpdate()->findOrFail($id);
    }

    public function create(int $userId, TaskCreateData $data): Task
    {
        $createData = $data->toArray();

        // Remove due_date from the 'create data' to handle it separately
        $dueDate = null;
        if (isset($createData['due_date'])) {
            $dueDate = $createData['due_date'];
            unset($createData['due_date']);
        }

        $task = Task::create([
            'user_id' => $userId,
            ...$createData,
        ]);

        if ($data->dueDate !== null) {
            $formattedDate = $data->dueDate->format('Y-m-d 00:00:00');

            DB::table('tasks')
                ->where('id', $task->id)
                ->update(['due_date' => $formattedDate]);

            $task->due_date = $data->dueDate;
        }

        $task->refresh();
        return $task;
    }

    public function update(Task $task, TaskUpdateData $data): Task
    {
        $updateData = array_filter(
            $data->toArray(),
            fn ($value) => !is_null($value)
        );

        // Remove due_date from the 'update data' to handle it separately
        $dueDate = null;
        if (isset($updateData['due_date'])) {
            $dueDate = $updateData['due_date'];
            unset($updateData['due_date']);
        }

        $task->update($updateData);

        if ($data->dueDate !== null) {
            $formattedDate = $data->dueDate->format('Y-m-d 00:00:00');

            DB::table('tasks')
                ->where('id', $task->id)
                ->update(['due_date' => $formattedDate]);

            $task->due_date = $data->dueDate;
        }

        $task->refresh();
        return $task;
    }

    public function delete(Task $task): void
    {
        $task->delete();
    }

    public function markAsComplete(Task $task): Task
    {
        $task->update([
            'status' => StatusEnum::DONE,
            'completed_at' => now(),
        ]);

        return $task;
    }

    public function hasIncompleteSubtasks(int $taskId, int $userId): bool
    {
        return Task::subtasksOf($taskId)
            ->forUser($userId)
            ->incomplete()
            ->exists();
    }

    private function applyFilters(Builder $query, ?TaskFiltersData $filters): void
    {
        if ($filters === null) {
            return;
        }

        $query
            ->when($filters->priority, fn ($q) => $q->byPriority($filters->priority))
            ->when($filters->status, fn ($q) => $q->byStatus($filters->status))
            ->when($filters->title, fn ($q) => $q->withTitleContaining($filters->title))
            ->when($filters->description, fn ($q) => $q->withDescriptionContaining($filters->description))
            ->when($filters->dueDate, fn ($q) => $q->dueOn($filters->dueDate))
            ->when($filters->completedAt, fn ($q) => $q->completedOn($filters->completedAt));
    }

    private function applySorting(Builder $query, ?TaskSortingData $sort): void
    {
        $query->when($sort, function ($q) use ($sort) {
            foreach ($sort->sorts as $sortData) {
                $q->orderByField($sortData['field']->value, $sortData['direction']);
            }
        });
    }
}
