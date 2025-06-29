<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\TaskResponseData;
use App\Http\Requests\CreateTaskRequest;
use App\Http\Requests\TaskFiltersRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

readonly class TaskController
{
    public function __construct(
        private TaskService $taskService,
    ) {
    }

    public function index(TaskFiltersRequest $request): JsonResponse
    {
        try {
            $userId = Auth::id();
            $filters = $request->toFiltersData();
            $sorting = $request->toSortingData();

            $tasks = $this->taskService->getTasks($userId, $filters, $sorting);
            $taskData = $tasks->map(function ($task) {
                $data = TaskResponseData::fromModel($task)->toArray();

                // Ensure date fields are properly formatted
                if ($task->due_date) {
                    $data['due_date'] = $task->due_date->format('Y-m-d');
                }

                if ($task->completed_at) {
                    $data['completed_at'] = $task->completed_at->format('Y-m-d');
                }

                return $data;
            });

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

            $responseData = TaskResponseData::fromModel($task)->toArray();

            // Use the due_date from the request if it was provided
            if ($request->has('due_date')) {
                $responseData['due_date'] = $request->input('due_date');
            } elseif ($task->due_date) {
                $responseData['due_date'] = $task->due_date->format('Y-m-d');
            }

            if ($task->completed_at) {
                $responseData['completed_at'] = $task->completed_at->format('Y-m-d');
            }

            return response()->json($responseData, Response::HTTP_CREATED);
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

            $responseData = TaskResponseData::fromModel($task)->toArray();

            // Ensure date fields are properly formatted
            if ($task->due_date) {
                $responseData['due_date'] = $task->due_date->format('Y-m-d');
            }

            if ($task->completed_at) {
                $responseData['completed_at'] = $task->completed_at->format('Y-m-d');
            }

            return response()->json($responseData, Response::HTTP_OK);
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
            $responseData = TaskResponseData::fromModel($task)->toArray();

            // Use the due_date from the request if it was provided
            if ($request->has('due_date')) {
                $responseData['due_date'] = $request->input('due_date');
            }

            return response()->json($responseData, Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Error updating task:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

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

            $responseData = TaskResponseData::fromModel($task)->toArray();

            // Ensure date fields are properly formatted
            if ($task->due_date) {
                $responseData['due_date'] = $task->due_date->format('Y-m-d');
            }

            if ($task->completed_at) {
                $responseData['completed_at'] = $task->completed_at->format('Y-m-d');
            }

            return response()->json($responseData, Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], $e instanceof ValidationException ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_NOT_FOUND);
        }
    }
}
