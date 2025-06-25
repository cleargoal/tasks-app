<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Data\TaskCreateData;
use App\Data\TaskFiltersData;
use App\Data\TaskSortingData;
use App\Data\TaskUpdateData;
use App\Enums\StatusEnum;
use App\Exceptions\TaskOperationException;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TaskRepository
{
    private const int TRANSACTION_TIMEOUT = 5;

    private function queryForUser(User $user): Builder
    {
        return Task::where('user_id', $user->id);
    }

    public function getByFiltersAndSort(User $user, ?TaskFiltersData $filters = null, ?TaskSortingData $sort = null): Collection
    {
        $query = $this->queryForUser($user);

        $this->applyFilters($query, $filters);
        $this->applySorting($query, $sort);

        return $query->get();
    }

    private function applyFilters(Builder $query, ?TaskFiltersData $filters): void
    {
        if ($filters === null) {
            return;
        }

        $query
            ->when($filters->priority, fn($q) => $q->where('priority', $filters->priority->value))
            ->when($filters->status, fn($q) => $q->where('status', $filters->status->value))
            ->when($filters->title, fn($q) => $this->applyTextSearch($q, 'title', $filters->title))
            ->when($filters->description, fn($q) => $this->applyTextSearch($q, 'description', $filters->description))
            ->when($filters->dueDate, fn($q) => $q->whereDate('due_date', $filters->dueDate->toDateString()))
            ->when($filters->completedAt, fn($q) => $q->whereDate('completed_at', $filters->completedAt->toDateString()));
    }

    private function applyTextSearch(Builder $query, string $field, string $value): Builder
    {
        return $query->where($field, 'like', '%' . $value . '%');
    }

    private function applySorting(Builder $query, ?TaskSortingData $sort): void
    {
        $query->when($sort, function ($q) use ($sort) {
            foreach ($sort->sorts as $sortData) {
                $q->orderBy($sortData['field']->value, $sortData['direction']);
            }
        });
    }

    public function create(User $user, TaskCreateData $data): Task
    {
        return Task::create([
            'user_id' => $user->id,
            ...$data->toArray(),
        ]);
    }

    public function findById(User $user, int $id): Task
    {
        return $this->queryForUser($user)->findOrFail($id);
    }

    public function update(User $user, int $id, TaskUpdateData $data): Task
    {
        $task = $this->findById($user, $id);

        $updateData = array_filter(
            $data->toArray(),
            fn($value) => !is_null($value)
        );
        $task->update($updateData);

        return $task;
    }

    public function delete(User $user, int $id): void
    {
        $task = $this->findById($user, $id);
        $task->delete();
    }

    public function markAsComplete(User $user, int $id): Task
    {
        return DB::transaction(function () use ($user, $id) {
            $task = $this->queryForUser($user)
                ->lockForUpdate()
                ->findOrFail($id);

            if ($this->hasIncompleteSubtasks($task)) {
                throw new TaskOperationException('Cannot complete task with incomplete subtasks');
            }

            if ($task->status === StatusEnum::TODO) {
                $task->update([
                    'status' => StatusEnum::DONE,
                    'completed_at' => now(),
                ]);
            }

            return $task;
        }, self::TRANSACTION_TIMEOUT);
    }

    private function hasIncompleteSubtasks(Task $task): bool
    {
        return Task::where('parent_id', $task->id)
            ->where('status', StatusEnum::TODO->value)
            ->exists();
    }
}
