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
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Service class for task-related operations.
 *
 * This class handles business logic for tasks including validation rules,
 * task creation, updates, deletion, and completion. It acts as an intermediary
 * between controllers and the repository layer.
 */
class TaskService
{
    /**
     * Maximum number of transaction attempts for operations that require database locks.
     */
    private const int MAX_TRANSACTION_ATTEMPTS = 5;

    /**
     * TaskService constructor.
     *
     * @param TaskRepository $taskRepository The repository for task data operations
     */
    public function __construct(
        private readonly TaskRepository $taskRepository,
    ) {
    }

    /**
     * Get a collection of tasks for a user with optional filtering and sorting.
     *
     * @param int $userId The ID of the user who owns the tasks
     * @param TaskFiltersData|null $filters Optional filters to apply to the task query
     * @param TaskSortingData|null $sort Optional sorting parameters for the task query
     * @return Collection<int, Task> A collection of Task models matching the criteria
     */
    public function getTasks(int $userId, ?TaskFiltersData $filters = null, ?TaskSortingData $sort = null): Collection
    {
        return $this->taskRepository->getByFiltersAndSort($userId, $filters, $sort);
    }

    /**
     * Create a new task for a user.
     *
     * @param int $userId The ID of the user who will own the task
     * @param TaskCreateData $data The data for creating the task
     * @return Task The newly created task
     * @throws ValidationException If the task data fails validation rules
     */
    public function createTask(int $userId, TaskCreateData $data): Task
    {
        $this->validateTaskCreate($userId, $data);

        return $this->taskRepository->create($userId, $data);
    }

    /**
     * Validate task creation data.
     *
     * Checks if the parent task exists and is not completed.
     *
     * @param int $userId The ID of the user who will own the task
     * @param TaskCreateData $data The data for creating the task
     * @throws ValidationException If the parent task is completed or doesn't exist
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
            } catch (ModelNotFoundException $e) {
                throw ValidationException::withMessages([
                    'message' => 'Parent task not found'
                ]);
            }
        }
    }

    /**
     * Get a specific task by ID.
     *
     * @param int $userId The ID of the user who owns the task
     * @param int $taskId The ID of the task to retrieve
     * @return Task The requested task
     * @throws ModelNotFoundException If the task is not found
     */
    public function getTask(int $userId, int $taskId): Task
    {
        return $this->taskRepository->findById($userId, $taskId);
    }

    /**
     * Update an existing task.
     *
     * @param int $userId The ID of the user who owns the task
     * @param int $taskId The ID of the task to update
     * @param TaskUpdateData $data The data for updating the task
     * @return Task The updated task
     * @throws ValidationException If the task data fails validation rules
     * @throws ModelNotFoundException If the task is not found
     */
    public function updateTask(int $userId, int $taskId, TaskUpdateData $data): Task
    {
        $task = $this->taskRepository->findById($userId, $taskId);
        $this->validateTaskUpdate($userId, $task, $data);

        return $this->taskRepository->update($task, $data);
    }

    /**
     * Validate task update data.
     *
     * Checks if:
     * - A task being marked as complete has no incomplete subtasks
     * - A new parent task exists and is not completed
     * - The task is not being set as its own parent
     *
     * @param int $userId The ID of the user who owns the task
     * @param Task $task The task being updated
     * @param TaskUpdateData $data The data for updating the task
     * @throws ValidationException If any validation rules are violated
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
            } catch (ModelNotFoundException $e) {
                throw ValidationException::withMessages([
                    'message' => 'Parent task not found'
                ]);
            }
        }
    }

    /**
     * Delete a task.
     *
     * @param int $userId The ID of the user who owns the task
     * @param int $taskId The ID of the task to delete
     * @throws ValidationException If the task cannot be deleted (e.g., it's completed)
     * @throws ModelNotFoundException If the task is not found
     */
    public function deleteTask(int $userId, int $taskId): void
    {
        $task = $this->taskRepository->findById($userId, $taskId);

        $this->validateTaskCanBeDeleted($task);

        $this->taskRepository->delete($task);
    }

    /**
     * Mark a task as complete.
     *
     * This operation is performed within a database transaction with a lock
     * to prevent race conditions when multiple users try to complete the same task.
     *
     * @param int $userId The ID of the user who owns the task
     * @param int $taskId The ID of the task to mark as complete
     * @return Task The completed task
     * @throws ModelNotFoundException If the task is not found
     */
    public function completeTask(int $userId, int $taskId): Task
    {
        return DB::transaction(function () use ($userId, $taskId) {
            $task = $this->taskRepository->findByIdWithLock($userId, $taskId);

            $this->validateTaskCanBeCompleted($task, $userId);

            return $this->taskRepository->markAsComplete($task);
        }, self::MAX_TRANSACTION_ATTEMPTS);
    }

    /**
     * Validate if a task can be deleted.
     *
     * Completed tasks cannot be deleted.
     *
     * @param Task $task The task to validate
     * @throws ValidationException If the task is completed
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
     * Validate if a task can be marked as complete.
     *
     * A task cannot be completed if:
     * - It is already completed
     * - It has incomplete subtasks
     *
     * @param Task $task The task to validate
     * @param int $userId The ID of the user who owns the task
     * @throws ValidationException If the task is already completed or has incomplete subtasks
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
