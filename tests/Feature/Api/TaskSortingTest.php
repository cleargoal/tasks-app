<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\PriorityEnum;
use App\Enums\StatusEnum;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskSortingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test')->plainTextToken;
    }

    public function test_sorts_tasks_by_title(): void
    {
        Task::factory()->for($this->user)->create(['title' => 'Task C']);
        Task::factory()->for($this->user)->create(['title' => 'Task A']);
        Task::factory()->for($this->user)->create(['title' => 'Task B']);

        $response = $this->getJson('/api/tasks?sort=title:asc', [
            'Authorization' => 'Bearer '.$this->token,
        ]);

        $response->assertOk();
        $tasks = $response->json();

        $this->assertEquals('Task A', $tasks[0]['title']);
        $this->assertEquals('Task B', $tasks[1]['title']);
        $this->assertEquals('Task C', $tasks[2]['title']);
    }

    public function test_sorts_tasks_by_priority_and_title(): void
    {
        Task::factory()->for($this->user)->create([
            'title' => 'Task B',
            'priority' => PriorityEnum::LOW->value,  // 5
        ]);
        Task::factory()->for($this->user)->create([
            'title' => 'Task A',
            'priority' => PriorityEnum::LOW->value,  // 5
        ]);
        Task::factory()->for($this->user)->create([
            'title' => 'Task C',
            'priority' => PriorityEnum::HIGH->value, // 1
        ]);

        $response = $this->getJson('/api/tasks?sort=priority:asc,title:asc', [
            'Authorization' => 'Bearer '.$this->token,
        ]);

        $response->assertOk();
        $tasks = $response->json();

        // HIGH priority (1) task should come first (priority ascending)
        $this->assertEquals('Task C', $tasks[0]['title']);
        // Then LOW priority (5) tasks ordered by title
        $this->assertEquals('Task A', $tasks[1]['title']);
        $this->assertEquals('Task B', $tasks[2]['title']);
    }

    public function test_sorts_tasks_by_status(): void
    {
        Task::factory()->for($this->user)->create([
            'title' => 'Task A',
            'status' => StatusEnum::DONE->value,
        ]);
        Task::factory()->for($this->user)->create([
            'title' => 'Task B',
            'status' => StatusEnum::TODO->value,
        ]);

        $response = $this->getJson('/api/tasks?sort=status:asc', [
            'Authorization' => 'Bearer '.$this->token,
        ]);

        $response->assertOk();
        $tasks = $response->json();

        $this->assertEquals(StatusEnum::DONE->value, $tasks[0]['status']);
        $this->assertEquals(StatusEnum::TODO->value, $tasks[1]['status']);
    }

    public function test_handles_invalid_sort_field(): void
    {
        Task::factory()->for($this->user)->create(['title' => 'Task A']);
        Task::factory()->for($this->user)->create(['title' => 'Task B']);

        $response = $this->getJson('/api/tasks?sort=invalid:asc', [
            'Authorization' => 'Bearer '.$this->token,
        ]);

        $response->assertOk();
        // Should return tasks without error, ignoring invalid sort
        $this->assertCount(2, $response->json());
    }
}
