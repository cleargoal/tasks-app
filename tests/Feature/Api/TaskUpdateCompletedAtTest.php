<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\PriorityEnum;
use App\Enums\StatusEnum;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskUpdateCompletedAtTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_updating_due_date_does_not_set_completed_at_for_incomplete_task(): void
    {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => StatusEnum::TODO,
            'due_date' => '2024-01-15 00:00:00',
            'completed_at' => null,
        ]);

        $newDueDate = '2024-01-20';

        $response = $this->actingAs($this->user)
            ->putJson("/api/tasks/{$task->id}", [
                'due_date' => $newDueDate,
            ]);

        $response->assertOk();

        $response->assertJsonPath('due_date', $newDueDate);

        $response->assertJsonPath('completed_at', null);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'due_date' => $newDueDate . ' 00:00:00',
            'completed_at' => null,
            'status' => StatusEnum::TODO->value,
        ]);
    }

    public function test_updating_due_date_preserves_existing_completed_at_for_completed_task(): void
    {
        $originalCompletedAt = '2024-01-10 14:30:00';

        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => StatusEnum::DONE,
            'due_date' => '2024-01-15 00:00:00',
            'completed_at' => $originalCompletedAt,
        ]);

        $newDueDate = '2024-01-20';

        $response = $this->actingAs($this->user)
            ->putJson("/api/tasks/{$task->id}", [
                'due_date' => $newDueDate,
            ]);

        $response->assertOk();

        $response->assertJsonPath('due_date', $newDueDate);

        $response->assertJsonPath('completed_at', '2024-01-10');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'due_date' => $newDueDate . ' 00:00:00',
            'completed_at' => $originalCompletedAt,
            'status' => StatusEnum::DONE->value,
        ]);
    }

    public function test_updating_title_does_not_set_completed_at_for_incomplete_task(): void
    {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => StatusEnum::TODO,
            'title' => 'Original Title',
            'completed_at' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/tasks/{$task->id}", [
                'title' => 'Updated Title',
            ]);

        $response->assertOk();

        $response->assertJsonPath('title', 'Updated Title');

        $response->assertJsonPath('completed_at', null);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated Title',
            'completed_at' => null,
            'status' => StatusEnum::TODO->value,
        ]);
    }

    public function test_updating_description_does_not_set_completed_at_for_incomplete_task(): void
    {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => StatusEnum::TODO,
            'description' => 'Original description',
            'completed_at' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/tasks/{$task->id}", [
                'description' => 'Updated description',
            ]);

        $response->assertOk();

        $response->assertJsonPath('description', 'Updated description');

        $response->assertJsonPath('completed_at', null);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'description' => 'Updated description',
            'completed_at' => null,
            'status' => StatusEnum::TODO->value,
        ]);
    }

    public function test_updating_priority_does_not_set_completed_at_for_incomplete_task(): void
    {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => StatusEnum::TODO,
            'completed_at' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/tasks/{$task->id}", [
                'priority' => PriorityEnum::HIGH->value,
            ]);

        $response->assertOk();

        $response->assertJsonPath('priority', PriorityEnum::HIGH->value);

        $response->assertJsonPath('completed_at', null);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'priority' => PriorityEnum::HIGH->value,
            'completed_at' => null,
            'status' => StatusEnum::TODO->value,
        ]);
    }

    public function test_updating_multiple_fields_does_not_set_completed_at_for_incomplete_task(): void
    {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => StatusEnum::TODO,
            'title' => 'Original Title',
            'due_date' => '2024-01-15 00:00:00',
            'completed_at' => null,
        ]);

        $newDueDate = '2024-01-25';

        $response = $this->actingAs($this->user)
            ->putJson("/api/tasks/{$task->id}", [
                'title' => 'Updated Title',
                'description' => 'Updated description',
                'due_date' => $newDueDate,
                'priority' => PriorityEnum::HIGH->value,
            ]);

        $response->assertOk();

        $response->assertJsonPath('title', 'Updated Title');
        $response->assertJsonPath('description', 'Updated description');
        $response->assertJsonPath('due_date', $newDueDate);
        $response->assertJsonPath('priority', PriorityEnum::HIGH->value);

        $response->assertJsonPath('completed_at', null);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated Title',
            'description' => 'Updated description',
            'due_date' => $newDueDate . ' 00:00:00',
            'priority' => PriorityEnum::HIGH->value,
            'completed_at' => null,
            'status' => StatusEnum::TODO->value,
        ]);
    }

    public function test_explicitly_setting_completed_at_to_null_keeps_it_null(): void
    {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => StatusEnum::TODO,
            'completed_at' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/tasks/{$task->id}", [
                'completed_at' => null,
            ]);

        $response->assertOk();

        $response->assertJsonPath('completed_at', null);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'completed_at' => null,
            'status' => StatusEnum::TODO->value,
        ]);
    }

    public function test_updating_status_to_done_without_completed_at_should_not_auto_set_completed_at(): void
    {
        // This test checks if changing status to DONE automatically sets completed_at

        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => StatusEnum::TODO,
            'completed_at' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/tasks/{$task->id}", [
                'status' => StatusEnum::DONE->value,
            ]);

        $response->assertOk();

        $response->assertJsonPath('status', StatusEnum::DONE->value);

        $response->assertJsonPath('completed_at', null);
    }

    public function test_set_task_completed(): void
    {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => StatusEnum::TODO,
            'completed_at' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/tasks/{$task->id}/complete");

        $response->assertOk();
        $response->assertJsonPath('status', StatusEnum::DONE->value);

        $json = $response->json();

        $this->assertNotNull($json['completed_at'], 'completed_at should not be null');
        $this->assertTrue(
            now()->isSameDay(Carbon::parse($json['completed_at'])),
            'completed_at is not today'
        );
    }
}
