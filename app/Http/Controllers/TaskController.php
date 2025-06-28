<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\TaskResponseData;
use App\Http\Requests\CreateTaskRequest;
use App\Http\Requests\TaskFiltersRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class TaskController
{
    public function __construct(
        private readonly TaskService $taskService,
    ) {
    }

    public function index(TaskFiltersRequest $request): JsonResponse
    {
        try {
            $userId = Auth::id();
            $filters = $request->toFiltersData();
            $sorting = $request->toSortingData();

            $tasks = $this->taskService->getTasks($userId, $filters, $sorting);
            $taskData = $tasks->map(fn ($task) => TaskResponseData::fromModel($task));

            return response()->json($taskData, Response::HTTP_OK);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function store(CreateTaskRequest $request): JsonResponse
    {
        try {
            $userId = Auth::id();
            $data = $request->toData();

            $task = $this->taskService->createTask($userId, $data);

            return response()->json(TaskResponseData::fromModel($task), Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $userId = Auth::id();
            $task = $this->taskService->getTask($userId, $id);

            return response()->json(TaskResponseData::fromModel($task), Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], $e instanceof ValidationException ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_NOT_FOUND);
        }
    }

    public function update(UpdateTaskRequest $request, int $id): JsonResponse
    {
        try {
            $userId = Auth::id();
            $data = $request->toData();

            $task = $this->taskService->updateTask($userId, $id, $data);

            return response()->json(TaskResponseData::fromModel($task), Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], $e instanceof ValidationException ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_NOT_FOUND);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $userId = Auth::id();
            $this->taskService->deleteTask($userId, $id);

            return response()->json(null, Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], $e instanceof ValidationException ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_NOT_FOUND);
        }
    }

    public function complete(int $id): JsonResponse
    {
        try {
            $userId = Auth::id();
            $task = $this->taskService->completeTask($userId, $id);

            return response()->json(TaskResponseData::fromModel($task), Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], $e instanceof ValidationException ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_NOT_FOUND);
        }
    }
}
