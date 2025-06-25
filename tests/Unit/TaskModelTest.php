<?php

namespace Tests\Unit;

use App\Enums\PriorityEnum;
use App\Enums\StatusEnum;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $task->user);
        $this->assertEquals($user->id, $task->user->id);
    }

    public function test_task_can_have_parent(): void
    {
        $user = User::factory()->create();
        $parentTask = Task::factory()->create(['user_id' => $user->id]);
        $childTask = Task::factory()->create([
            'user_id' => $user->id,
            'parent_id' => $parentTask->id,
        ]);

        $this->assertInstanceOf(Task::class, $childTask->parent);
        $this->assertEquals($parentTask->id, $childTask->parent->id);
    }

    public function test_task_can_query_children(): void
    {
        $user = User::factory()->create();
        $parentTask = Task::factory()->create(['user_id' => $user->id]);
        $childTask1 = Task::factory()->create([
            'user_id' => $user->id,
            'parent_id' => $parentTask->id,
        ]);
        $childTask2 = Task::factory()->create([
            'user_id' => $user->id,
            'parent_id' => $parentTask->id,
        ]);

        // Query children manually since the relationship might not exist
        $children = Task::where('parent_id', $parentTask->id)->get();
        $this->assertCount(2, $children);
        $this->assertTrue($children->contains('id', $childTask1->id));
        $this->assertTrue($children->contains('id', $childTask2->id));
    }

    public function test_task_has_correct_fillable_attributes(): void
    {
        $task = new Task;
        $expected = [
            'user_id',
            'parent_id',
            'title',
            'description',
            'status',
            'priority',
            'due_date',
            'completed_at',
        ];

        $this->assertEquals($expected, $task->getFillable());
    }

    public function test_task_casts_attributes_correctly(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create([
            'user_id' => $user->id,
            'status' => StatusEnum::TODO->value,
            'priority' => PriorityEnum::HIGH->value,
            'due_date' => '2025-06-22',
            'completed_at' => '2025-06-23 10:30:00',
        ]);

        $this->assertInstanceOf(StatusEnum::class, $task->status);
        $this->assertInstanceOf(PriorityEnum::class, $task->priority);
        $this->assertInstanceOf(Carbon::class, $task->due_date);
        $this->assertInstanceOf(Carbon::class, $task->completed_at);
    }

    public function test_task_factory_creates_valid_task(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user->id]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'user_id' => $user->id,
        ]);

        $this->assertNotEmpty($task->title);
        $this->assertInstanceOf(StatusEnum::class, $task->status);
        $this->assertInstanceOf(PriorityEnum::class, $task->priority);
    }

    public function test_task_parent_relationship_returns_null_when_no_parent(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create([
            'user_id' => $user->id,
            'parent_id' => null,
        ]);

        $this->assertNull($task->parent);
    }

    public function test_task_user_relationship_is_required(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $user->id]);

        $this->assertNotNull($task->user);
        $this->assertEquals($user->id, $task->user_id);
    }

    public function test_task_can_have_subtasks(): void
    {
        $user = User::factory()->create();
        $parentTask = Task::factory()->create(['user_id' => $user->id]);

        // Create subtasks
        $subtask1 = Task::factory()->create([
            'user_id' => $user->id,
            'parent_id' => $parentTask->id,
            'title' => 'Subtask 1',
        ]);

        $subtask2 = Task::factory()->create([
            'user_id' => $user->id,
            'parent_id' => $parentTask->id,
            'title' => 'Subtask 2',
        ]);

        // Verify subtasks exist
        $this->assertDatabaseHas('tasks', [
            'id' => $subtask1->id,
            'parent_id' => $parentTask->id,
        ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $subtask2->id,
            'parent_id' => $parentTask->id,
        ]);
    }
}
