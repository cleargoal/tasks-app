<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\TaskCreateData;
use App\Data\TaskIndexData;
use App\Data\TaskUpdateData;
use App\Exceptions\TaskOperationException;
use App\Services\TaskService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;

class TaskController extends Controller
{
    public function __construct(
        protected TaskService $service,
    ) {
    }

    public function index(TaskIndexData $data): JsonResponse
    {
        $tasks = $this->service->getAll($data);
        return response()->json($tasks);
    }

    public function store(TaskCreateData $data): JsonResponse
    {
        $task = $this->service->create($data);
        return response()->json($task, 201);
    }

    /**
     * @throws AuthenticationException
     */
    public function show(int $id): JsonResponse
    {
        $task = $this->service->getOneForUser($id);
        return response()->json($task);
    }

    /**
     * @throws AuthenticationException
     */
    public function update(int $id, TaskUpdateData $data): JsonResponse
    {
        $task = $this->service->update($id, $data);
        return response()->json($task);
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->delete($id);
            return response()->json(null, 204);
        } catch (TaskOperationException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function complete(int $id): JsonResponse
    {
        try {
            $task = $this->service->complete($id);
            return response()->json($task);
        } catch (TaskOperationException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
