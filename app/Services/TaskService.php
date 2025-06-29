<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\TaskCreateData;
use App\Data\TaskFiltersData;
use App\Data\TaskSortingData;
use App\Data\TaskUpdateData;
use App\Enums\StatusEnum;
use App\Models\Task;
use App\Repositories\TaskRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TaskService
{
    private const int MAX_TRANSACTION_ATTEMPTS = 5;

    public function __construct(
        private readonly TaskRepository $taskRepository,
    ) {
    }


    /**
     * @return Collection<int, Task>
     */
    public function getTasks(int $userId, ?TaskFiltersData $filters = null, ?TaskSortingData $sort = null): Collection
    {
        return $this->taskRepository->getByFiltersAndSort($userId, $filters, $sort);
    }

    /**
     * @throws ValidationException
     */
    public function createTask(int $userId, TaskCreateData $data): Task
    {
        $this->validateTaskCreate($userId, $data);

        return $this->taskRepository->create($userId, $data);
    }

    /**
     * @throws ValidationException
     */
    private function validateTaskCreate(int $userId, TaskCreateData $data): void
    {
        if ($data->parentId !== null) {
            try {
                $parentTask = $this->taskRepository->findById($userId, $data->parentId);
                if ($parentTask->status === StatusEnum::DONE) {
                    throw ValidationException::withMessages([
                        'message' => 'Cannot add subtask to a completed task'
                    ]);
                }
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                throw ValidationException::withMessages([
                    'message' => 'Parent task not found'
                ]);
            }
        }
    }

    public function getTask(int $userId, int $taskId): Task
    {
        return $this->taskRepository->findById($userId, $taskId);
    }

    /**
     * @throws ValidationException
     */
    public function updateTask(int $userId, int $taskId, TaskUpdateData $data): Task
    {
        $task = $this->taskRepository->findById($userId, $taskId);
        $this->validateTaskUpdate($userId, $task, $data);

        return $this->taskRepository->update($task, $data);
    }

    /**
     * @throws ValidationException
     */
    private function validateTaskUpdate(int $userId, Task $task, TaskUpdateData $data): void
    {
        if ($data->status === StatusEnum::DONE && $task->status !== StatusEnum::DONE) {
            // If trying to mark as complete, check for incomplete subtasks
            if ($this->taskRepository->hasIncompleteSubtasks($task->id, $userId)) {
                throw ValidationException::withMessages([
                    'message' => 'Cannot complete task with incomplete subtasks'
                ]);
            }
        }

        if ($data->parentId !== null && $data->parentId !== $task->parent_id) {
            try {
                $parentTask = $this->taskRepository->findById($userId, $data->parentId);

                // Check if the new parent task is not completed
                if ($parentTask->status === StatusEnum::DONE) {
                    throw ValidationException::withMessages([
                        'message' => 'Cannot move task under a completed parent task'
                    ]);
                }

                // Check for circular reference
                if ($data->parentId === $task->id) {
                    throw ValidationException::withMessages([
                        'message' => 'Task cannot be its own parent'
                    ]);
                }
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                throw ValidationException::withMessages([
                    'message' => 'Parent task not found'
                ]);
            }
        }
    }

    /**
     * @throws ValidationException
     */
    public function deleteTask(int $userId, int $taskId): void
    {
        $task = $this->taskRepository->findById($userId, $taskId);

        $this->validateTaskCanBeDeleted($task);

        $this->taskRepository->delete($task);
    }

    public function completeTask(int $userId, int $taskId): Task
    {
        return DB::transaction(function () use ($userId, $taskId) {
            $task = $this->taskRepository->findByIdWithLock($userId, $taskId);

            $this->validateTaskCanBeCompleted($task, $userId);

            return $this->taskRepository->markAsComplete($task);
        }, self::MAX_TRANSACTION_ATTEMPTS);
    }

    /**
     * @throws ValidationException
     */
    private function validateTaskCanBeDeleted(Task $task): void
    {
        if ($task->status === StatusEnum::DONE) {
            throw ValidationException::withMessages([
                'message' => 'Cannot delete completed tasks'
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    private function validateTaskCanBeCompleted(Task $task, int $userId): void
    {
        if ($task->status === StatusEnum::DONE) {
            throw ValidationException::withMessages([
                'message' => 'Task is already completed'
            ]);
        }

        if ($this->taskRepository->hasIncompleteSubtasks($task->id, $userId)) {
            throw ValidationException::withMessages([
                'message' => 'Cannot complete task with incomplete subtasks'
            ]);
        }
    }
}
