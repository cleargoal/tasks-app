<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\Task\TaskIndexData;
use App\Repositories\TaskRepository;
use Illuminate\Support\Collection;

class TaskService
{
    public function __construct(private TaskRepository $repository)
    {}

    public function getTasks(TaskIndexData $data): Collection
    {
        return $this->repository->getTasks($data);
    }
}
