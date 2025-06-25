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
use App\Models\User;
use App\Repositories\TaskRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

readonly class TaskService
{
    public function __construct(
        private TaskRepository $repository
    ) {}

    private function getAuthenticatedUser(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }

    public function getFiltered(?TaskFiltersData $filters, ?TaskSortingData $sort): Collection
    {
        $user = $this->getAuthenticatedUser();

        return $this->repository->getByFiltersAndSort($user, $filters, $sort);
    }

    public function create(TaskCreateData $data): Task
    {
        $user = $this->getAuthenticatedUser();

        return DB::transaction(fn () => $this->repository->create($user, $data));
    }

    public function update(int $taskId, TaskUpdateData $data): Task
    {
        $user = $this->getAuthenticatedUser();

        return DB::transaction(fn () => $this->repository->update($user, $taskId, $data));
    }

    public function findById(int $id): Task
    {
        $user = $this->getAuthenticatedUser();

        return $this->repository->findById($user, $id);
    }

    public function delete(int $taskId): void
    {
        $user = $this->getAuthenticatedUser();

        DB::transaction(function () use ($user, $taskId) {
            $task = $this->repository->findById($user, $taskId);

            if ($task->status === StatusEnum::DONE) {
                throw new TaskOperationException('Cannot delete completed tasks');
            }

            $this->repository->delete($user, $taskId);
        });
    }

    public function markAsComplete(int $taskId): Task
    {
        $user = $this->getAuthenticatedUser();

        return $this->repository->markAsComplete($user, $taskId);
    }
}
