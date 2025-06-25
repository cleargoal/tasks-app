<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\TaskCreateData;
use App\Data\TaskIndexData;
use App\Data\TaskUpdateData;
use App\Http\Requests\TaskIndexRequest;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;

class TaskController extends Controller
{
    public function __construct(
        protected readonly TaskService $service,
    ) {
    }

    public function index(TaskIndexRequest $request): JsonResponse
    {
        $data = TaskIndexData::from($request->validated());
        $tasks = $this->service->getFiltered($data->filters, $data->sort);
        return response()->json($tasks);
    }

    public function store(TaskCreateData $data): JsonResponse
    {
        $task = $this->service->create($data);
        return response()->json($task, 201);
    }

    public function show(int $id): JsonResponse
    {
        $task = $this->service->findById($id);
        return response()->json($task);
    }

    public function update(int $id, TaskUpdateData $data): JsonResponse
    {
        $task = $this->service->update($id, $data);
        return response()->json($task);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);
        return response()->json(null, 204);
    }

    public function complete(int $id): JsonResponse
    {
        $task = $this->service->markAsComplete($id);
        return response()->json($task);
    }
}
