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
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Repository for task data operations.
 *
 * This class handles all database interactions for tasks, including
 * retrieving, creating, updating, and deleting tasks. It also provides
 * methods for filtering and sorting tasks.
 */
class TaskRepository
{
    /**
     * Get tasks for a user with optional filtering and sorting.
     *
     * @param int $userId The ID of the user who owns the tasks
     * @param TaskFiltersData|null $filters Optional filters to apply to the task query
     * @param TaskSortingData|null $sort Optional sorting parameters for the task query
     * @return Collection A collection of Task models matching the criteria
     */
    public function getByFiltersAndSort(int $userId, ?TaskFiltersData $filters = null, ?TaskSortingData $sort = null): Collection
    {
        $query = Task::forUser($userId);

        $this->applyFilters($query, $filters);
        $this->applySorting($query, $sort);

        return $query->get();
    }

    /**
     * Find a task by ID for a specific user.
     *
     * @param int $userId The ID of the user who owns the task
     * @param int $id The ID of the task to find
     * @return Task The found task
     * @throws ModelNotFoundException If the task is not found
     */
    public function findById(int $userId, int $id): Task
    {
        return Task::forUser($userId)->findOrFail($id);
    }

    /**
     * Find a task by ID with a database lock for a specific user.
     *
     * This method is used for operations that require exclusive access to the task record,
     * such as completing a task, to prevent race conditions.
     *
     * @param int $userId The ID of the user who owns the task
     * @param int $id The ID of the task to find
     * @return Task The found task with a database lock
     * @throws ModelNotFoundException If the task is not found
     */
    public function findByIdWithLock(int $userId, int $id): Task
    {
        return Task::forUser($userId)->lockForUpdate()->findOrFail($id);
    }

    /**
     * Create a new task.
     *
     * This method handles special formatting for date fields to ensure
     * they are stored correctly in the database.
     *
     * @param int $userId The ID of the user who will own the task
     * @param TaskCreateData $data The data for creating the task
     * @return Task The newly created task
     */
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
            $formattedDate = $data->dueDate->format('Y-m-d');

            DB::table('tasks')
                ->where('id', $task->id)
                ->update(['due_date' => $formattedDate]);

            $task->due_date = $data->dueDate;
        }

        $task->refresh();
        return $task;
    }

    /**
     * Update an existing task.
     *
     * This method handles special formatting for date fields to ensure
     * they are stored correctly in the database. It also filters out null values
     * to avoid overwriting existing data with nulls.
     *
     * @param Task $task The task to update
     * @param TaskUpdateData $data The data for updating the task
     * @return Task The updated task
     */
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
            $formattedDate = $data->dueDate->format('Y-m-d');

            DB::table('tasks')
                ->where('id', $task->id)
                ->update(['due_date' => $formattedDate]);

            $task->due_date = $data->dueDate;
        }

        $task->refresh();
        return $task;
    }

    /**
     * Delete a task.
     *
     * @param Task $task The task to delete
     */
    public function delete(Task $task): void
    {
        $task->delete();
    }

    /**
     * Mark a task as complete.
     *
     * Updates the task status to DONE and sets the completed_at timestamp.
     *
     * @param Task $task The task to mark as complete
     * @return Task The updated task
     */
    public function markAsComplete(Task $task): Task
    {
        $task->update([
            'status' => StatusEnum::DONE,
            'completed_at' => now(),
        ]);

        return $task;
    }

    /**
     * Check if a task has any incomplete subtasks.
     *
     * @param int $taskId The ID of the parent task
     * @param int $userId The ID of the user who owns the tasks
     * @return bool True if the task has incomplete subtasks, false otherwise
     */
    public function hasIncompleteSubtasks(int $taskId, int $userId): bool
    {
        return Task::subtasksOf($taskId)
            ->forUser($userId)
            ->incomplete()
            ->exists();
    }

    /**
     * Apply filters to the query
     *
     * @param Builder $query The query builder instance
     * @param TaskFiltersData|null $filters The filters to apply
     */
    private function applyFilters(Builder $query, ?TaskFiltersData $filters): void
    {
        if ($filters === null) {
            return;
        }

        $query
            /** @phpstan-ignore-next-line */
            ->when($filters->priority, fn (Builder $q) => $q->byPriority($filters->priority))
            /** @phpstan-ignore-next-line */
            ->when($filters->status, fn (Builder $q) => $q->byStatus($filters->status))
            /** @phpstan-ignore-next-line */
            ->when($filters->title, fn (Builder $q) => $q->withTitleContaining($filters->title))
            /** @phpstan-ignore-next-line */
            ->when($filters->description, fn (Builder $q) => $q->withDescriptionContaining($filters->description))
            /** @phpstan-ignore-next-line */
            ->when($filters->dueDate, fn (Builder $q) => $q->dueOn($filters->dueDate))
            /** @phpstan-ignore-next-line */
            ->when($filters->completedAt, fn (Builder $q) => $q->completedOn($filters->completedAt));
    }

    /**
     * Apply sorting to the query
     *
     * @param Builder $query The query builder instance
     * @param TaskSortingData|null $sort The sorting options to apply
     */
    private function applySorting(Builder $query, ?TaskSortingData $sort): void
    {
        $query->when($sort, function (Builder $q) use ($sort) {
            foreach ($sort->sorts as $sortData) {
                /** @phpstan-ignore-next-line */
                $q->orderByField($sortData['field']->value, $sortData['direction']);
            }
        });
    }
}
