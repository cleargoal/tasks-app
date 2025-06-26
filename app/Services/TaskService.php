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
use Illuminate\Validation\ValidationException;

class TaskService
{
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
        return $this->taskRepository->update($userId, $taskId, $data);
    }

    /**
     * @throws ValidationException
     */
    public function deleteTask(int $userId, int $taskId): void
    {
        $task = $this->taskRepository->findById($userId, $taskId);

        if ($task->status === StatusEnum::DONE) {
            throw ValidationException::withMessages([
                'message' => 'Cannot delete completed tasks'
            ]);
        }

        $this->taskRepository->delete($userId, $taskId);
    }

    /**
     * @throws ValidationException
     */
    public function completeTask(int $userId, int $taskId): Task
    {
        $task = $this->taskRepository->findById($userId, $taskId);

        // Check if task has incomplete subtasks
        $incompleteSubtasks = Task::where('parent_id', $taskId)
            ->where('user_id', $userId)
            ->where('status', StatusEnum::TODO)
            ->exists();

        if ($incompleteSubtasks) {
            throw ValidationException::withMessages([
                'message' => 'Cannot complete task with incomplete subtasks'
            ]);
        }

        return $this->taskRepository->markAsComplete($userId, $taskId);
    }
}
