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
    private const int TRANSACTION_TIMEOUT = 5;

    public function __construct(
        private readonly TaskRepository $taskRepository,
    ) {
    }

    public function getTasks(int $userId, ?TaskFiltersData $filters = null, ?TaskSortingData $sort = null): Collection
    {
        return $this->taskRepository->getByFiltersAndSort($userId, $filters, $sort);
    }

    public function createTask(int $userId, TaskCreateData $data): Task
    {
        return $this->taskRepository->create($userId, $data);
    }

    public function getTask(int $userId, int $taskId): Task
    {
        return $this->taskRepository->findById($userId, $taskId);
    }

    public function updateTask(int $userId, int $taskId, TaskUpdateData $data): Task
    {
        $task = $this->taskRepository->findById($userId, $taskId);
        return $this->taskRepository->update($task, $data);
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
        }, self::TRANSACTION_TIMEOUT);
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
