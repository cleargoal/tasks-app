<?php

declare(strict_types=1);

namespace Tests\Unit\Data;

use App\Data\TaskSortingData;
use App\Enums\TaskSortFieldEnum;
use PHPUnit\Framework\TestCase;

class TaskSortingDataTest extends TestCase
{
    public function testCreatesEmptySortingDataWhenStringIsEmpty(): void
    {
        $sortData = TaskSortingData::fromString('');
        $this->assertEmpty($sortData->sorts);
    }

    public function testCreatesSortingDataFromSingleField(): void
    {
        $sortData = TaskSortingData::fromString('title:asc');

        $this->assertCount(1, $sortData->sorts);
        $this->assertEquals(TaskSortFieldEnum::TITLE, $sortData->sorts[0]['field']);
        $this->assertEquals('asc', $sortData->sorts[0]['direction']);
    }

    public function testCreatesSortingDataFromMultipleFields(): void
    {
        $sortData = TaskSortingData::fromString('title:asc,priority:desc');

        $this->assertCount(2, $sortData->sorts);
        $this->assertEquals(TaskSortFieldEnum::TITLE, $sortData->sorts[0]['field']);
        $this->assertEquals('asc', $sortData->sorts[0]['direction']);
        $this->assertEquals(TaskSortFieldEnum::PRIORITY, $sortData->sorts[1]['field']);
        $this->assertEquals('desc', $sortData->sorts[1]['direction']);
    }

    public function testDefaultsToAscWhenDirectionIsInvalid(): void
    {
        $sortData = TaskSortingData::fromString('title:invalid');

        $this->assertCount(1, $sortData->sorts);
        $this->assertEquals('asc', $sortData->sorts[0]['direction']);
    }

    public function testIgnoresInvalidFields(): void
    {
        $sortData = TaskSortingData::fromString('invalid:asc,title:desc');

        $this->assertCount(1, $sortData->sorts);
        $this->assertEquals(TaskSortFieldEnum::TITLE, $sortData->sorts[0]['field']);
    }

    public function testHandlesDefaultDirectionWhenNotProvided(): void
    {
        $sortData = TaskSortingData::fromString('title');

        $this->assertCount(1, $sortData->sorts);
        $this->assertEquals(TaskSortFieldEnum::TITLE, $sortData->sorts[0]['field']);
        $this->assertEquals('asc', $sortData->sorts[0]['direction']);
    }
}
