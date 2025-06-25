<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\PriorityEnum;
use App\Enums\StatusEnum;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskFilteringTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
    }

    /** @test */
    public function it_filters_tasks_by_completed_at_date(): void
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

        Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => StatusEnum::TODO,
            'completed_at' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/tasks?filters[completedAt]=2024-03-15');

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.id', $completedToday->id);
        $response->assertJsonPath('0.completed_at', '2024-03-15T10:00:00.000000Z');
    }

    /** @test */
    public function it_filters_multiple_tasks_completed_on_same_date(): void
    {
        $task1 = Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => StatusEnum::DONE,
            'completed_at' => '2024-03-15 09:00:00',
        ]);

        $task2 = Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => StatusEnum::DONE,
            'completed_at' => '2024-03-15 18:30:00',
        ]);

        Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => StatusEnum::DONE,
            'completed_at' => '2024-03-16 12:00:00',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/tasks?filters[completedAt]=2024-03-15');

        $response->assertOk();
        $response->assertJsonCount(2);

        $taskIds = collect($response->json())->pluck('id')->sort()->values();
        $this->assertEquals([$task1->id, $task2->id], $taskIds->toArray());
    }

    /** @test */
    public function it_returns_empty_when_filtering_todo_tasks_by_completed_at(): void
    {
        Task::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'status' => StatusEnum::TODO,
            'completed_at' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/tasks?filters[completedAt]=2024-03-15');

        $response->assertOk();
        $response->assertJsonCount(0);
    }

    /** @test */
    public function it_filters_by_status_and_completed_at_combined(): void
    {
        $completedTask = Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => StatusEnum::DONE,
            'completed_at' => '2024-03-15 12:00:00',
        ]);

        Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => StatusEnum::TODO,
            'completed_at' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/tasks?filters[status]=done&filters[completedAt]=2024-03-15');

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.id', $completedTask->id);
        $response->assertJsonPath('0.status', 'done');
    }

    /** @test */
    public function it_filters_completed_at_with_other_filters(): void
    {
        $highPriorityCompleted = Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Important Task',
            'priority' => PriorityEnum::HIGH,  // Fixed: Use HIGH instead of HIGHEST
            'status' => StatusEnum::DONE,
            'completed_at' => '2024-03-15 12:00:00',
        ]);

        Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Regular Task',
            'priority' => PriorityEnum::LOW,   // Fixed: Use LOW instead of LOWEST
            'status' => StatusEnum::DONE,
            'completed_at' => '2024-03-15 15:00:00',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/tasks?filters[completedAt]=2024-03-15&filters[priority]=1&filters[title]=Important');

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.id', $highPriorityCompleted->id);
        $response->assertJsonPath('0.title', 'Important Task');
        $response->assertJsonPath('0.priority', 1);
    }

    /** @test */
    public function it_only_shows_user_own_tasks_when_filtering_by_completed_at(): void
    {
        $userTask = Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => StatusEnum::DONE,
            'completed_at' => '2024-03-15 12:00:00',
        ]);

        Task::factory()->create([
            'user_id' => $this->otherUser->id,
            'status' => StatusEnum::DONE,
            'completed_at' => '2024-03-15 14:00:00',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/tasks?filters[completedAt]=2024-03-15');

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.id', $userTask->id);
        $response->assertJsonPath('0.user_id', $this->user->id);
    }

    /** @test */
    public function it_sorts_filtered_completed_tasks_correctly(): void
    {
        $laterTask = Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Later Task',
            'status' => StatusEnum::DONE,
            'completed_at' => '2024-03-15 18:00:00',
        ]);

        $earlierTask = Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Earlier Task',
            'status' => StatusEnum::DONE,
            'completed_at' => '2024-03-15 09:00:00',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/tasks?filters[completedAt]=2024-03-15&sort=completed_at:asc');

        $response->assertOk();
        $response->assertJsonCount(2);
        $response->assertJsonPath('0.id', $earlierTask->id);
        $response->assertJsonPath('1.id', $laterTask->id);
    }

    /** @test */
    public function it_returns_empty_for_non_existent_completed_date(): void
    {
        Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => StatusEnum::DONE,
            'completed_at' => '2024-03-14 12:00:00',
        ]);

        Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => StatusEnum::DONE,
            'completed_at' => '2024-03-16 12:00:00',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/tasks?filters[completedAt]=2024-03-15');

        $response->assertOk();
        $response->assertJsonCount(0);
    }

    /** @test */
    public function it_handles_invalid_completed_at_date_format(): void
    {
        Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => StatusEnum::DONE,
            'completed_at' => '2024-03-15 12:00:00',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/tasks?filters[completedAt]=invalid-date');

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'filters.completedAt',
                ],
            ]);

        $responseData = $response->json();
        $this->assertEquals(
            'The completed date filter must be in YYYY-MM-DD format.',
            $responseData['errors']['filters.completedAt'][0]
        );
    }

    /** @test */
    public function it_handles_invalid_due_date_format(): void
    {
        Task::factory()->create([
            'user_id' => $this->user->id,
            'due_date' => '2024-03-15',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/tasks?filters[dueDate]=not-a-date');

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'filters.dueDate',
                ],
            ]);

        $responseData = $response->json();
        $this->assertEquals(
            'The due date filter must be in YYYY-MM-DD format.',
            $responseData['errors']['filters.dueDate'][0]
        );
    }

    /** @test */
    public function it_handles_invalid_priority_values(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/tasks?filters[priority]=6');

        $response->assertStatus(422);

        $responseData = $response->json();
        $this->assertEquals(
            'Priority filter must be between 1 and 5.',
            $responseData['errors']['filters.priority'][0]
        );
    }

    /** @test */
    public function it_handles_invalid_status_values(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/tasks?filters[status]=invalid');

        $response->assertStatus(422);

        $responseData = $response->json();
        $this->assertEquals(
            'Status filter must be either "todo" or "done".',
            $responseData['errors']['filters.status'][0]
        );
    }

    /** @test */
    public function it_filters_by_due_date_and_completed_at_separately(): void
    {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'due_date' => '2024-03-10',
            'status' => StatusEnum::DONE,
            'completed_at' => '2024-03-15 12:00:00',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/tasks?filters[dueDate]=2024-03-10');

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.id', $task->id);

        $response = $this->actingAs($this->user)
            ->getJson('/api/tasks?filters[completedAt]=2024-03-15');

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.id', $task->id);

        $response = $this->actingAs($this->user)
            ->getJson('/api/tasks?filters[completedAt]=2024-03-10');

        $response->assertOk();
        $response->assertJsonCount(0);
    }
}
