<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Data\Task\TaskIndexData;
use App\Services\TaskService;

class TaskController extends Controller
{
    public function index(TaskIndexData $data, TaskService $taskService)
    {
        $tasks = $taskService->getTasks($data);
        return response()->json($tasks);
    }
}
