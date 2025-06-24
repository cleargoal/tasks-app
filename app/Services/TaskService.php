<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\TaskCreateData;
use App\Data\TaskFiltersData;
use App\Data\TaskSortingData;
use App\Data\TaskUpdateData;
use App\Enums\StatusEnum;
use App\Exceptions\TaskOperationException;
use App\Models\Task;
use App\Repositories\TaskRepository;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Collection;

readonly class TaskService
{
    public function __construct(
        private TaskRepository $repository
    ) {
    }

    /**
     * @throws AuthenticationException
     */
    public function getByFiltersAndSort(?TaskFiltersData $filters, ?TaskSortingData $sort): Collection
    {
        return $this->repository->getByFiltersAndSort($filters, $sort);
    }

    /**
     * @throws AuthenticationException
     */
    public function create(TaskCreateData $data): Task
    {
        return $this->repository->createForUser($data);
    }

    /**
     * @throws AuthenticationException
     */
    public function update(int $taskId, TaskUpdateData $data): Task
    {
        return $this->repository->updateForUser($taskId, $data);
    }

    /**
     * @throws AuthenticationException
     */
    public function getOneForUser(int $id): Task
    {
        return $this->repository->findOrFailForUser($id);
    }

    /**
     * @throws TaskOperationException
     * @throws AuthenticationException
     */
    public function delete(int $taskId): void
    {
        $task = $this->repository->findOrFailForUser($taskId);

        if ($task->status === StatusEnum::DONE) {
            throw new TaskOperationException('Cannot delete completed tasks');
        }

        $this->repository->deleteForUser($taskId);
    }

    /**
     * @throws TaskOperationException
     * @throws AuthenticationException
     */
    public function complete(int $taskId): Task
    {
        return $this->repository->completeTask($taskId);
    }
}
