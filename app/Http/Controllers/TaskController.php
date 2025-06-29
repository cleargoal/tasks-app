<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\TaskResponseData;
use App\Http\Requests\CreateTaskRequest;
use App\Http\Requests\TaskFiltersRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Services\TaskService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

readonly class TaskController
{
    /**
     * TaskController constructor.
     *
     * @param TaskService $taskService The service for handling task operations
     */
    public function __construct(
        private TaskService $taskService,
    ) {
    }

    /**
     * Get a list of tasks with optional filtering and sorting.
     *
     * @param TaskFiltersRequest $request The request containing filter and sorting parameters
     * @return JsonResponse The JSON response containing the list of tasks
     */
    public function index(TaskFiltersRequest $request): JsonResponse
    {
        try {
            $userId = Auth::id();
            $filters = $request->toFiltersData();
            $sorting = $request->toSortingData();

            $tasks = $this->taskService->getTasks($userId, $filters, $sorting);

            // Map tasks to TaskResponseData objects and collect them into a DataCollection
            $taskDataCollection = TaskResponseData::collect(
                $tasks->map(fn ($task) => TaskResponseData::fromModel($task))
            );

            return response()->json($taskDataCollection->toArray(), Response::HTTP_OK);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Create a new task.
     *
     * @param CreateTaskRequest $request The request containing task data
     * @return TaskResponseData|JsonResponse The created task data or error response
     */
    public function store(CreateTaskRequest $request): TaskResponseData | JsonResponse
    {
        try {
            $userId = Auth::id();
            $data = $request->toData();

            $task = $this->taskService->createTask($userId, $data);

            return TaskResponseData::fromModel($task);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Get a specific task by ID.
     *
     * @param int $id The ID of the task to retrieve
     * @return JsonResponse The JSON response containing the task data
     * @throws Exception If the task is not found
     */
    public function show(int $id): JsonResponse
    {
        try {
            $userId = Auth::id();
            $task = $this->taskService->getTask($userId, $id);

            $responseData = TaskResponseData::fromModel($task);
            return response()->json($responseData->toArray(), Response::HTTP_OK);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Update an existing task.
     *
     * @param UpdateTaskRequest $request The request containing updated task data
     * @param int $id The ID of the task to update
     * @return JsonResponse The JSON response containing the updated task data
     * @throws Exception If the task is not found or cannot be updated
     */
    public function update(UpdateTaskRequest $request, int $id): JsonResponse
    {
        try {
            $userId = Auth::id();
            $data = $request->toData();
            $task = $this->taskService->updateTask($userId, $id, $data);

            $responseData = TaskResponseData::fromModel($task);

            return response()->json($responseData->toArray(), Response::HTTP_OK);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            Log::error('Error updating task:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => $e->getMessage()
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Delete a task.
     *
     * @param int $id The ID of the task to delete
     * @return JsonResponse The JSON response with no content on success
     * @throws Exception If the task is not found
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $userId = Auth::id();
            $this->taskService->deleteTask($userId, $id);

            return response()->json(null, Response::HTTP_NO_CONTENT);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Mark a task as complete.
     *
     * @param int $id The ID of the task to mark as complete
     * @return JsonResponse The JSON response containing the completed task data
     * @throws Exception If the task is not found
     */
    public function complete(int $id): JsonResponse
    {
        try {
            $userId = Auth::id();
            $task = $this->taskService->completeTask($userId, $id);

            $responseData = TaskResponseData::fromModel($task);
            return response()->json($responseData->toArray(), Response::HTTP_OK);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], Response::HTTP_NOT_FOUND);
        }
    }
}
