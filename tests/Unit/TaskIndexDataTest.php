<?php

namespace Tests\Unit;

use App\Data\TaskFiltersData;
use App\Data\TaskIndexData;
use App\Data\TaskSortingData;
use App\Enums\PriorityEnum;
use App\Enums\StatusEnum;
use Carbon\Carbon;
use Tests\TestCase;

class TaskIndexDataTest extends TestCase
{
    public function test_task_index_data_creation_with_no_parameters(): void
    {
        $data = new TaskIndexData();

        $this->assertNull($data->filters);
        $this->assertNull($data->sort);
    }

    public function test_task_index_data_creation_with_filters_only(): void
    {
        $filters = new TaskFiltersData(
            priority: PriorityEnum::HIGH,
            status: StatusEnum::TODO,
            title: 'Test Task',
            description: 'Test Description',
            dueDate: Carbon::parse('2025-06-22'),
            completedAt: null
        );

        $data = new TaskIndexData(filters: $filters);

        $this->assertInstanceOf(TaskFiltersData::class, $data->filters);
        $this->assertEquals(PriorityEnum::HIGH, $data->filters->priority);
        $this->assertEquals(StatusEnum::TODO, $data->filters->status);
        $this->assertEquals('Test Task', $data->filters->title);
        $this->assertNull($data->sort);
    }

    public function test_task_index_data_creation_with_sorting_only(): void
    {
        $sorting = TaskSortingData::fromString('title:asc,priority:desc');

        $data = new TaskIndexData(sort: $sorting);

        $this->assertInstanceOf(TaskSortingData::class, $data->sort);
        $this->assertNull($data->filters);
    }

    public function test_task_index_data_creation_with_filters_and_sorting(): void
    {
        $filters = new TaskFiltersData(
            priority: PriorityEnum::LOW,
            status: StatusEnum::DONE,
            title: null,
            description: null,
            dueDate: null,
            completedAt: Carbon::parse('2025-06-15')
        );

        $sorting = TaskSortingData::fromString('created_at:desc');

        $data = new TaskIndexData(
            filters: $filters,
            sort: $sorting
        );

        $this->assertInstanceOf(TaskFiltersData::class, $data->filters);
        $this->assertInstanceOf(TaskSortingData::class, $data->sort);
        $this->assertEquals(PriorityEnum::LOW, $data->filters->priority);
        $this->assertEquals(StatusEnum::DONE, $data->filters->status);
        $this->assertEquals('2025-06-15', $data->filters->completedAt->toDateString());
    }

    public function test_task_index_data_with_empty_filters(): void
    {
        $filters = new TaskFiltersData(
            priority: null,
            status: null,
            title: null,
            description: null,
            dueDate: null,
            completedAt: null
        );

        $data = new TaskIndexData(filters: $filters);

        $this->assertInstanceOf(TaskFiltersData::class, $data->filters);
        $this->assertNull($data->filters->priority);
        $this->assertNull($data->filters->status);
        $this->assertNull($data->filters->title);
        $this->assertNull($data->filters->description);
        $this->assertNull($data->filters->dueDate);
        $this->assertNull($data->filters->completedAt);
    }
}
