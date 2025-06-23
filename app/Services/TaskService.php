<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\TaskCreateData;
use App\Data\TaskIndexData;
use App\Data\TaskUpdateData;
use App\Models\Task;
use App\Repositories\TaskRepository;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Collection;

class TaskService
{
    public function __construct(
        protected TaskRepository $repository,
    )
    {
    }

    /**
     * @throws AuthenticationException
     */
    public function getAll(TaskIndexData $data): Collection
    {
        return $this->repository->getByFiltersAndSort($data->filters, $data->sort);
    }

    public function create(TaskCreateData $data): Task
    {
        return $this->repository->createForUser(auth()->id(), $data);
    }

    /**
     * @throws AuthenticationException
     */
    public function getOneForUser(int $id): Task
    {
        return $this->repository->findOrFailForUser($id);
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
    public function delete(int $taskId): void
    {
        $this->repository->deleteForUser($taskId);
    }

    public function complete(int $id): Task
    {
        return $this->repository->completeTask($id);
    }
}
