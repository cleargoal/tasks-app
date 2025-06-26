<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\TaskCreateData;
use App\Data\TaskFiltersData;
use App\Data\TaskResponseData;
use App\Data\TaskSortingData;
use App\Data\TaskUpdateData;
use App\Http\Requests\TaskIndexRequest;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class TaskController
{
    public function __construct(
        private readonly TaskService $taskService,
    ) {
    }

    public function index(TaskIndexRequest $request): Collection
    {
        $userId = Auth::id();
        $filters = TaskFiltersData::from($request->input('filters', []));
        $sorting = TaskSortingData::from($request->input('sort'));

        $tasks = $this->taskService->getTasks($userId, $filters, $sorting);

        return $tasks->map(fn ($task) => TaskResponseData::fromModel($task));
    }

    public function store(Request $request): TaskResponseData
    {
        $userId = Auth::id();
        $data = TaskCreateData::from($request);

        $task = $this->taskService->createTask($userId, $data);

        return TaskResponseData::fromModel($task);
    }

    public function show(int $id): TaskResponseData
    {
        $userId = Auth::id();
        $task = $this->taskService->getTask($userId, $id);

        return TaskResponseData::fromModel($task);
    }

    public function update(Request $request, int $id): TaskResponseData
    {
        $userId = Auth::id();
        $data = TaskUpdateData::from($request);

        $task = $this->taskService->updateTask($userId, $id, $data);

        return TaskResponseData::fromModel($task);
    }

    public function destroy(int $id): JsonResponse
    {
        $userId = Auth::id();

        try {
            $this->taskService->deleteTask($userId, $id);
            return response()->json(null, Response::HTTP_NO_CONTENT);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function complete(int $id): JsonResponse
    {
        $userId = Auth::id();

        try {
            $task = $this->taskService->completeTask($userId, $id);
            return response()->json(TaskResponseData::fromModel($task), Response::HTTP_OK);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
