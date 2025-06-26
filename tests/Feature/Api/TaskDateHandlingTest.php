<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\StatusEnum;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskDateHandlingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_it_handles_date_only_format_when_creating_task(): void
    {
        $tomorrow = Carbon::tomorrow()->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->postJson('/api/tasks', [
                'title' => 'Test Task',
                'due_date' => $tomorrow,
            ]);

        $response->assertCreated();
        $response->assertJsonPath('due_date', $tomorrow);

        $this->assertDatabaseHas('tasks', [
            'id' => $response->json('id'),
            'due_date' => $tomorrow.' 00:00:00',
        ]);
    }

    public function test_it_handles_date_only_format_when_updating_task(): void
    {
        $pastDate = '2024-01-15';
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'due_date' => $pastDate.' 00:00:00',
        ]);

        $newDate = '2024-01-16';
        $response = $this->actingAs($this->user)
            ->putJson("/api/tasks/{$task->id}", [
                'due_date' => $newDate,
            ]);

        $response->assertOk();
        $response->assertJsonPath('due_date', $newDate);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'due_date' => $newDate.' 00:00:00',
        ]);
    }

    public function test_it_filters_tasks_by_date_only_format(): void
    {
        $matchingTask = Task::factory()->create([
            'user_id' => $this->user->id,
            'due_date' => '2024-03-15 00:00:00',
        ]);

        $nonMatchingTask = Task::factory()->create([
            'user_id' => $this->user->id,
            'due_date' => '2024-03-16 00:00:00',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/tasks?filters[dueDate]=2024-03-15');

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.id', $matchingTask->id);
        $response->assertJsonPath('0.due_date', '2024-03-15');
    }

    public function test_it_filters_tasks_by_completed_at_date(): void
    {
        $completedYesterday = Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => StatusEnum::DONE,
            'completed_at' => '2024-03-14 15:30:00',
        ]);

        $completedToday = Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => StatusEnum::DONE,
            'completed_at' => '2024-03-15 10:00:00',
        ]);

        $todoTask = Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => StatusEnum::TODO,
            'completed_at' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/tasks?filters[completedAt]=2024-03-15');

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.id', $completedToday->id);
        $response->assertJsonPath('0.completed_at', '2024-03-15');
    }
}
