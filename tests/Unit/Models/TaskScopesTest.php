<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\PriorityEnum;
use App\Enums\StatusEnum;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskScopesTest extends TestCase
{
    use RefreshDatabase;

    private User $user1;
    private User $user2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user1 = User::factory()->create();
        $this->user2 = User::factory()->create();
    }

    public function test_scope_for_user(): void
    {
        $user1Tasks = Task::factory()->count(3)->create(['user_id' => $this->user1->id]);
        $user2Tasks = Task::factory()->count(2)->create(['user_id' => $this->user2->id]);

        $result = Task::forUser($this->user1->id)->get();

        $this->assertCount(3, $result);
        foreach ($result as $task) {
            $this->assertEquals($this->user1->id, $task->user_id);
        }
    }

    public function test_scope_by_priority(): void
    {
        Task::factory()->count(2)->create([
            'user_id' => $this->user1->id,
            'priority' => PriorityEnum::LOW,
        ]);

        Task::factory()->count(3)->create([
            'user_id' => $this->user1->id,
            'priority' => PriorityEnum::MID,
        ]);

        Task::factory()->count(1)->create([
            'user_id' => $this->user1->id,
            'priority' => PriorityEnum::HIGH,
        ]);

        $result = Task::byPriority(PriorityEnum::MID)->get();

        $this->assertCount(3, $result);
        foreach ($result as $task) {
            $this->assertEquals(PriorityEnum::MID, $task->priority);
        }
    }

    public function test_scope_by_status(): void
    {
        Task::factory()->count(4)->create([
            'user_id' => $this->user1->id,
            'status' => StatusEnum::TODO,
        ]);

        Task::factory()->count(2)->create([
            'user_id' => $this->user1->id,
            'status' => StatusEnum::DONE,
        ]);

        $result = Task::byStatus(StatusEnum::DONE)->get();

        $this->assertCount(2, $result);
        foreach ($result as $task) {
            $this->assertEquals(StatusEnum::DONE, $task->status);
        }
    }

    public function test_scope_with_title_containing(): void
    {
        Task::factory()->create([
            'user_id' => $this->user1->id,
            'title' => 'Project Alpha',
        ]);

        Task::factory()->create([
            'user_id' => $this->user1->id,
            'title' => 'Project Beta',
        ]);

        Task::factory()->create([
            'user_id' => $this->user1->id,
            'title' => 'Task Gamma',
        ]);

        $result = Task::withTitleContaining('Project')->get();

        $this->assertCount(2, $result);
        foreach ($result as $task) {
            $this->assertStringContainsString('Project', $task->title);
        }
    }

    public function test_scope_with_description_containing(): void
    {
        Task::factory()->create([
            'user_id' => $this->user1->id,
            'description' => 'This is an urgent task',
        ]);

        Task::factory()->create([
            'user_id' => $this->user1->id,
            'description' => 'This is a normal task',
        ]);

        Task::factory()->create([
            'user_id' => $this->user1->id,
            'description' => 'No urgency here',
        ]);

        $result = Task::withDescriptionContaining('urgent')->get();

        $this->assertCount(1, $result);
        $this->assertStringContainsString('urgent', $result->first()->description);
    }

    public function test_scope_due_on(): void
    {
        $today = Carbon::today();
        $tomorrow = Carbon::tomorrow();
        $yesterday = Carbon::yesterday();

        Task::factory()->create([
            'user_id' => $this->user1->id,
            'due_date' => $today,
        ]);

        Task::factory()->create([
            'user_id' => $this->user1->id,
            'due_date' => $tomorrow,
        ]);

        Task::factory()->create([
            'user_id' => $this->user1->id,
            'due_date' => $yesterday,
        ]);

        $result = Task::dueOn($today)->get();

        $this->assertCount(1, $result);
        $this->assertTrue($today->isSameDay($result->first()->due_date));
    }

    public function test_scope_completed_on(): void
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        Task::factory()->create([
            'user_id' => $this->user1->id,
            'status' => StatusEnum::DONE,
            'completed_at' => $today,
        ]);

        Task::factory()->create([
            'user_id' => $this->user1->id,
            'status' => StatusEnum::DONE,
            'completed_at' => $yesterday,
        ]);

        Task::factory()->create([
            'user_id' => $this->user1->id,
            'status' => StatusEnum::TODO,
            'completed_at' => null,
        ]);

        $result = Task::completedOn($today)->get();

        $this->assertCount(1, $result);
        $this->assertTrue($today->isSameDay($result->first()->completed_at));
    }

    public function test_scope_incomplete(): void
    {
        Task::factory()->count(3)->create([
            'user_id' => $this->user1->id,
            'status' => StatusEnum::TODO,
        ]);

        Task::factory()->count(2)->create([
            'user_id' => $this->user1->id,
            'status' => StatusEnum::DONE,
        ]);

        $result = Task::incomplete()->get();

        $this->assertCount(3, $result);
        foreach ($result as $task) {
            $this->assertEquals(StatusEnum::TODO, $task->status);
        }
    }

    public function test_scope_completed(): void
    {
        Task::factory()->count(3)->create([
            'user_id' => $this->user1->id,
            'status' => StatusEnum::TODO,
        ]);

        Task::factory()->count(2)->create([
            'user_id' => $this->user1->id,
            'status' => StatusEnum::DONE,
        ]);

        $result = Task::completed()->get();

        $this->assertCount(2, $result);
        foreach ($result as $task) {
            $this->assertEquals(StatusEnum::DONE, $task->status);
        }
    }

    public function test_scope_subtasks_of(): void
    {
        $parentTask = Task::factory()->create([
            'user_id' => $this->user1->id,
        ]);

        Task::factory()->count(3)->create([
            'user_id' => $this->user1->id,
            'parent_id' => $parentTask->id,
        ]);

        Task::factory()->count(2)->create([
            'user_id' => $this->user1->id,
            'parent_id' => null,
        ]);

        $result = Task::subtasksOf($parentTask->id)->get();

        $this->assertCount(3, $result);
        foreach ($result as $task) {
            $this->assertEquals($parentTask->id, $task->parent_id);
        }
    }

    public function test_scope_order_by_field(): void
    {
        $laterDate = Carbon::today()->addDays(5);
        $earlierDate = Carbon::today()->addDays(2);
        $middleDate = Carbon::today()->addDays(3);

        Task::factory()->create([
            'user_id' => $this->user1->id,
            'title' => 'Task C',
            'due_date' => $laterDate,
        ]);

        Task::factory()->create([
            'user_id' => $this->user1->id,
            'title' => 'Task A',
            'due_date' => $earlierDate,
        ]);

        Task::factory()->create([
            'user_id' => $this->user1->id,
            'title' => 'Task B',
            'due_date' => $middleDate,
        ]);

        $resultAsc = Task::orderByField('due_date', 'asc')->get();

        $this->assertCount(3, $resultAsc);
        $this->assertEquals('Task A', $resultAsc[0]->title);
        $this->assertEquals('Task B', $resultAsc[1]->title);
        $this->assertEquals('Task C', $resultAsc[2]->title);

        $resultDesc = Task::orderByField('due_date', 'desc')->get();

        $this->assertCount(3, $resultDesc);
        $this->assertEquals('Task C', $resultDesc[0]->title);
        $this->assertEquals('Task B', $resultDesc[1]->title);
        $this->assertEquals('Task A', $resultDesc[2]->title);
    }

    public function test_combining_multiple_scopes(): void
    {
        Task::factory()->create([
            'user_id' => $this->user1->id,
            'status' => StatusEnum::TODO,
            'priority' => PriorityEnum::HIGH,
            'title' => 'Important task',
        ]);

        Task::factory()->create([
            'user_id' => $this->user1->id,
            'status' => StatusEnum::DONE,
            'priority' => PriorityEnum::HIGH,
            'title' => 'Completed important task',
        ]);

        Task::factory()->create([
            'user_id' => $this->user2->id,
            'status' => StatusEnum::TODO,
            'priority' => PriorityEnum::HIGH,
            'title' => 'User2 important task',
        ]);

        Task::factory()->create([
            'user_id' => $this->user1->id,
            'status' => StatusEnum::TODO,
            'priority' => PriorityEnum::LOW,
            'title' => 'Low priority task',
        ]);

        $result = Task::forUser($this->user1->id)
            ->incomplete()
            ->byPriority(PriorityEnum::HIGH)
            ->get();

        $this->assertCount(1, $result);
        $this->assertEquals($this->user1->id, $result->first()->user_id);
        $this->assertEquals(StatusEnum::TODO, $result->first()->status);
        $this->assertEquals(PriorityEnum::HIGH, $result->first()->priority);
        $this->assertEquals('Important task', $result->first()->title);
    }
}
