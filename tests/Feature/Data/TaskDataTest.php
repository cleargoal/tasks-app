<?php

declare(strict_types=1);

namespace Tests\Feature\Data;

use App\Data\TaskCreateData;
use App\Data\TaskUpdateData;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskDataTest extends TestCase
{
    use RefreshDatabase;

    public function task_create_data_formats_due_date_correctly(): void
    {
        $data = new TaskCreateData(
            title: 'Test Task',
            description: 'Test Description',
            dueDate: Carbon::create(2024, 3, 15, 14, 30, 0)
        );

        $arrayData = $data->toArray();

        $this->assertEquals('2024-03-15', $arrayData['dueDate']);
    }

    public function task_update_data_formats_due_date_correctly(): void
    {
        $data = new TaskUpdateData(
            title: 'Updated Task',
            dueDate: Carbon::create(2024, 3, 15, 14, 30, 0)
        );

        $arrayData = $data->toArray();

        $this->assertEquals('2024-03-15', $arrayData['dueDate']);
    }

    public function task_data_handles_null_due_date(): void
    {
        $createData = new TaskCreateData(
            title: 'Test Task',
            description: 'Test Description'
        );

        $updateData = new TaskUpdateData(
            title: 'Updated Task'
        );

        $this->assertNull($createData->toArray()['dueDate']);
        $this->assertNull($updateData->toArray()['dueDate']);
    }
}
