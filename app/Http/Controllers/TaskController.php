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

    public function store(CreateTaskRequest $request): JsonResponse
    {
        try {
            $userId = Auth::id();
            $data = $request->toData();

            $task = $this->taskService->createTask($userId, $data);

            $responseData = TaskResponseData::fromModel($task);

            // If we need to use the due_date from the request, we need to create a custom response
            if ($request->has('due_date')) {
                $data = $responseData->toArray();
                $data['due_date'] = $request->input('due_date');
                return response()->json($data, Response::HTTP_CREATED);
            }

            // For POST requests, we need to specify the CREATED status code
            return response()->json($responseData->toArray(), Response::HTTP_CREATED);
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

    public function update(UpdateTaskRequest $request, int $id): JsonResponse
    {
        try {
            $userId = Auth::id();
            $data = $request->toData();
            $task = $this->taskService->updateTask($userId, $id, $data);

            $responseData = TaskResponseData::fromModel($task);

            // If we need to use the due_date from the request, we need to create a custom response
            if ($request->has('due_date')) {
                $data = $responseData->toArray();
                $data['due_date'] = $request->input('due_date');
                return response()->json($data, Response::HTTP_OK);
            }

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
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], Response::HTTP_NOT_FOUND);
        }
    }

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
