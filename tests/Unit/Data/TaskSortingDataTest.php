<?php

declare(strict_types=1);

namespace Tests\Unit\Data;

use App\Data\TaskSortingData;
use App\Enums\TaskSortFieldEnum;
use PHPUnit\Framework\TestCase;

class TaskSortingDataTest extends TestCase
{
    public function test_creates_empty_sorting_data_when_string_is_empty(): void
    {
        $sortData = TaskSortingData::fromString('');
        $this->assertEmpty($sortData->sorts);
    }

    public function test_creates_sorting_data_from_single_field(): void
    {
        $sortData = TaskSortingData::fromString('title:asc');

        $this->assertCount(1, $sortData->sorts);
        $this->assertEquals(TaskSortFieldEnum::TITLE, $sortData->sorts[0]['field']);
        $this->assertEquals('asc', $sortData->sorts[0]['direction']);
    }

    public function test_creates_sorting_data_from_multiple_fields(): void
    {
        $sortData = TaskSortingData::fromString('title:asc,priority:desc');

        $this->assertCount(2, $sortData->sorts);
        $this->assertEquals(TaskSortFieldEnum::TITLE, $sortData->sorts[0]['field']);
        $this->assertEquals('asc', $sortData->sorts[0]['direction']);
        $this->assertEquals(TaskSortFieldEnum::PRIORITY, $sortData->sorts[1]['field']);
        $this->assertEquals('desc', $sortData->sorts[1]['direction']);
    }

    public function test_defaults_to_asc_when_direction_is_invalid(): void
    {
        $sortData = TaskSortingData::fromString('title:invalid');

        $this->assertCount(1, $sortData->sorts);
        $this->assertEquals('asc', $sortData->sorts[0]['direction']);
    }

    public function test_ignores_invalid_fields(): void
    {
        $sortData = TaskSortingData::fromString('invalid:asc,title:desc');

        $this->assertCount(1, $sortData->sorts);
        $this->assertEquals(TaskSortFieldEnum::TITLE, $sortData->sorts[0]['field']);
    }

    public function test_handles_default_direction_when_not_provided(): void
    {
        $sortData = TaskSortingData::fromString('title');

        $this->assertCount(1, $sortData->sorts);
        $this->assertEquals(TaskSortFieldEnum::TITLE, $sortData->sorts[0]['field']);
        $this->assertEquals('asc', $sortData->sorts[0]['direction']);
    }
}
