<?php

namespace Tests\Feature;

use App\Enums\StatusEnum;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskOperationsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_cannot_delete_completed_task(): void
    {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => StatusEnum::DONE->value,
            'completed_at' => now(),
        ]);

        $response = $this
            ->actingAs($this->user)
            ->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Cannot delete completed tasks'
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id
        ]);
    }

    public function test_can_delete_incomplete_task(): void
    {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => StatusEnum::TODO->value,
        ]);

        $response = $this
            ->actingAs($this->user)
            ->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('tasks', [
            'id' => $task->id
        ]);
    }

    public function test_cannot_complete_task_with_incomplete_subtasks(): void
    {
        $parentTask = Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => StatusEnum::TODO->value,
            'completed_at' => null,
        ]);

        Task::factory()->create([
            'user_id' => $this->user->id,
            'parent_id' => $parentTask->id,
            'status' => StatusEnum::TODO->value,
        ]);

        $response = $this
            ->actingAs($this->user)
            ->postJson("/api/tasks/{$parentTask->id}/complete");

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Cannot complete task with incomplete subtasks'
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $parentTask->id,
            'status' => StatusEnum::TODO->value,
        ]);

        $updatedTask = Task::find($parentTask->id);
        $this->assertEquals(StatusEnum::TODO, $updatedTask->status);
    }

    public function test_can_complete_task_with_completed_subtasks(): void
    {
        $parentTask = Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => StatusEnum::TODO->value,
        ]);

        Task::factory()->create([
            'user_id' => $this->user->id,
            'parent_id' => $parentTask->id,
            'status' => StatusEnum::DONE->value,
            'completed_at' => now(),
        ]);

        $response = $this
            ->actingAs($this->user)
            ->postJson("/api/tasks/{$parentTask->id}/complete");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'status',
                'completed_at'
            ])
            ->assertJson([
                'id' => $parentTask->id,
                'status' => StatusEnum::DONE->value,
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $parentTask->id,
            'status' => StatusEnum::DONE->value,
        ]);

        $this->assertNotNull(Task::find($parentTask->id)->completed_at);
    }

    public function test_can_complete_task_without_subtasks(): void
    {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => StatusEnum::TODO->value,
        ]);

        $response = $this
            ->actingAs($this->user)
            ->postJson("/api/tasks/{$task->id}/complete");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'status',
                'completed_at'
            ])
            ->assertJson([
                'id' => $task->id,
                'status' => StatusEnum::DONE->value,
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => StatusEnum::DONE->value,
        ]);

        $this->assertNotNull(Task::find($task->id)->completed_at);
    }
}
