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
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TaskRepository
{
    /**
     * @throws AuthenticationException
     */
    public function queryForUser()
    {
        $userId = Auth::id();

        if (!$userId) {
            throw new AuthenticationException('User is not authenticated.');
        }

        return Task::where('user_id', $userId);
    }

    /**
     * @throws AuthenticationException
     */
    public function getByFiltersAndSort(?TaskFiltersData $filters, ?TaskSortingData $sort): Collection
    {
        $query = $this->queryForUser();

        if ($filters !== null) {
            if ($filters->priority !== null) {
                $query->where('priority', $filters->priority->value);
            }

            if ($filters->status !== null) {
                $query->where('status', $filters->status->value);
            }

            if ($filters->title !== null) {
                $query->where('title', 'like', '%' . $filters->title . '%');
            }

            if ($filters->description !== null) {
                $query->where('description', 'like', '%' . $filters->description . '%');
            }
        }

        if ($sort !== null) {
            foreach ($sort->sorts as $sortData) {
                $query->orderBy($sortData['field']->value, $sortData['direction']);
            }
        }

        return $query->get();
    }

    /**
     * @throws AuthenticationException
     */
    public function createForUser(TaskCreateData $data): Task
    {
        $userId = Auth::id();

        if (!$userId) {
            throw new AuthenticationException('User is not authenticated.');
        }

        return Task::create([
            'user_id' => $userId,
            ...$data->toArray(),
        ]);
    }

    /**
     * @throws AuthenticationException
     */
    public function findOrFailForUser(int $id): Task
    {
        return $this->queryForUser()->findOrFail($id);
    }

    /**
     * @throws AuthenticationException
     */
    public function updateForUser(int $id, TaskUpdateData $data): Task
    {
        $task = $this->findOrFailForUser($id);

        $updateData = array_filter(
            $data->toArray(),
            fn($value) => !is_null($value)
        );
        $task->update($updateData);

        return $task;
    }

    /**
     * @throws AuthenticationException
     */
    public function deleteForUser(int $id): void
    {
        $task = $this->findOrFailForUser($id);
        $task->delete();
    }

    /**
     * @throws AuthenticationException
     * @throws TaskOperationException
     */
    public function completeTask(int $id): Task
    {
        return DB::transaction(function () use ($id) {
            $task = $this->queryForUser()
                ->lockForUpdate()
                ->findOrFail($id);

            if (Task::where('parent_id', $task->id)
                ->where('status', StatusEnum::TODO->value)
                ->exists()
            ) {
                throw new TaskOperationException('Cannot complete task with incomplete subtasks');
            }

            if ($task->status === StatusEnum::TODO) {
                $task->update([
                    'status' => StatusEnum::DONE,
                    'completed_at' => now(),
                ]);
            }

            return $task;
        }, 5);
    }
}
