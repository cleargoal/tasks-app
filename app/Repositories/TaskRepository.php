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

class TaskRepository
{
    public function getByFiltersAndSort(int $userId, ?TaskFiltersData $filters = null, ?TaskSortingData $sort = null): Collection
    {
        $query = Task::where('user_id', $userId);

        $this->applyFilters($query, $filters);
        $this->applySorting($query, $sort);

        return $query->get();
    }

    public function findById(int $userId, int $id): Task
    {
        return Task::where('user_id', $userId)->findOrFail($id);
    }

    public function findByIdWithLock(int $userId, int $id): Task
    {
        return Task::where('user_id', $userId)->lockForUpdate()->findOrFail($id);
    }

    public function create(int $userId, TaskCreateData $data): Task
    {
        return Task::create([
            'user_id' => $userId,
            ...$data->toArray(),
        ]);
    }

    public function update(Task $task, TaskUpdateData $data): Task
    {
        $updateData = array_filter(
            $data->toArray(),
            fn ($value) => !is_null($value)
        );

        $task->update($updateData);
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
        return Task::where('parent_id', $taskId)
            ->where('user_id', $userId)
            ->where('status', StatusEnum::TODO)
            ->exists();
    }

    private function applyFilters(Builder $query, ?TaskFiltersData $filters): void
    {
        if ($filters === null) {
            return;
        }

        $query
            ->when($filters->priority, fn ($q) => $q->where('priority', $filters->priority->value))
            ->when($filters->status, fn ($q) => $q->where('status', $filters->status->value))
            ->when($filters->title, fn ($q) => $this->applyTextSearch($q, 'title', $filters->title))
            ->when($filters->description, fn ($q) => $this->applyTextSearch($q, 'description', $filters->description))
            ->when($filters->dueDate, fn ($q) => $q->whereDate('due_date', $filters->dueDate->toDateString()))
            ->when($filters->completedAt, fn ($q) => $q->whereDate('completed_at', $filters->completedAt->toDateString()));
    }

    private function applyTextSearch(Builder $query, string $field, string $value): Builder
    {
        return $query->where($field, 'like', '%'.$value.'%');
    }

    private function applySorting(Builder $query, ?TaskSortingData $sort): void
    {
        $query->when($sort, function ($q) use ($sort) {
            foreach ($sort->sorts as $sortData) {
                $q->orderBy($sortData['field']->value, $sortData['direction']);
            }
        });
    }
}
