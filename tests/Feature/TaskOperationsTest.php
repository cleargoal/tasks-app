<?php

namespace Tests\Feature;

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
            'status' => 'done',
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
            'status' => 'todo',
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
        // Create parent task
        $parentTask = Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'todo',
            'completed_at' => null,
        ]);

        // Create incomplete subtask
        Task::factory()->create([
            'user_id' => $this->user->id,
            'parent_id' => $parentTask->id,
            'status' => 'todo',
        ]);

        $response = $this
            ->actingAs($this->user)
            ->postJson("/api/tasks/{$parentTask->id}/complete");

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Cannot complete task with incomplete subtasks'
            ]);

        // Verify parent task remains in todo status
        $this->assertDatabaseHas('tasks', [
            'id' => $parentTask->id,
            'status' => 'todo',
        ]);

        // Verify the task in the database is still the one we expect
        $updatedTask = Task::find($parentTask->id);
        $this->assertEquals('todo', $updatedTask->status->value);
    }

    public function test_can_complete_task_with_completed_subtasks(): void
    {
        // Create parent task
        $parentTask = Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'todo',
        ]);

        // Create completed subtask
        Task::factory()->create([
            'user_id' => $this->user->id,
            'parent_id' => $parentTask->id,
            'status' => 'done',
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
                'status' => 'done',
            ]);

        // Verify parent task is now complete
        $this->assertDatabaseHas('tasks', [
            'id' => $parentTask->id,
            'status' => 'done',
        ]);

        // Verify completed_at is set
        $this->assertNotNull(Task::find($parentTask->id)->completed_at);
    }

    public function test_can_complete_task_without_subtasks(): void
    {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'todo',
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
                'status' => 'done',
            ]);

        // Verify task is complete
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'done',
        ]);

        // Verify completed_at is set
        $this->assertNotNull(Task::find($task->id)->completed_at);
    }
}
