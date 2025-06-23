<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Testing\Fluent\AssertableJson;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;

class TaskDateHandlingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function it_handles_date_only_format_when_creating_task(): void
    {
        $tomorrow = Carbon::tomorrow()->format('Y-m-d');

        $response = $this->actingAs($this->user)
            ->postJson('/api/tasks', [
                'title' => 'Test Task',
                'due_date' => $tomorrow
            ]);

        $response->assertCreated();
        $response->assertJsonPath('due_date', $tomorrow . 'T00:00:00.000000Z');

        $this->assertDatabaseHas('tasks', [
            'id' => $response->json('id'),
            'due_date' => $tomorrow . ' 00:00:00'
        ]);
    }

    #[Test]
    public function it_handles_date_only_format_when_updating_task(): void
    {
        // We can use any date for existing task
        $pastDate = '2024-01-15';
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'due_date' => $pastDate . ' 00:00:00'
        ]);

        // And we can update to any other date
        $newDate = '2024-01-16';
        $response = $this->actingAs($this->user)
            ->putJson("/api/tasks/{$task->id}", [
                'due_date' => $newDate
            ]);

        $response->assertOk();
        $response->assertJsonPath('due_date', $newDate . 'T00:00:00.000000Z');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'due_date' => $newDate . ' 00:00:00'
        ]);
    }

    public function it_filters_tasks_by_date_only_format(): void
    {
        $matchingTask = Task::factory()->create([
            'user_id' => $this->user->id,
            'due_date' => '2024-03-15 00:00:00'
        ]);

        $nonMatchingTask = Task::factory()->create([
            'user_id' => $this->user->id,
            'due_date' => '2024-03-16 00:00:00'
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/tasks?filters[dueDate]=2024-03-15');

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.id', $matchingTask->id);
        $response->assertJsonPath('0.due_date', '2024-03-15T00:00:00.000000Z');
    }
}
