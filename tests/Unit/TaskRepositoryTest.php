<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Data\TaskFiltersData;
use App\Enums\StatusEnum;
use App\Models\Task;
use App\Models\User;
use App\Repositories\TaskRepository;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private TaskRepository $repository;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new TaskRepository();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_applies_completed_at_filter_correctly(): void
    {
        // Create tasks with different completion dates
        $targetDate = Carbon::parse('2024-03-15');

        $matchingTask = Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => StatusEnum::DONE,
            'completed_at' => '2024-03-15 12:00:00'
        ]);

        $nonMatchingTask = Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => StatusEnum::DONE,
            'completed_at' => '2024-03-16 12:00:00'
        ]);

        $todoTask = Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => StatusEnum::TODO,
            'completed_at' => null
        ]);

        // Create filter with completed_at date
        $filters = new TaskFiltersData(
            priority: null,
            status: null,
            title: null,
            description: null,
            dueDate: null,
            completedAt: $targetDate
        );

        $result = $this->repository->getByFiltersAndSort($this->user, $filters);

        $this->assertCount(1, $result);
        $this->assertEquals($matchingTask->id, $result->first()->id);
    }

    /** @test */
    public function it_returns_all_tasks_when_completed_at_filter_is_null(): void
    {
        Task::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        $filters = new TaskFiltersData(
            priority: null,
            status: null,
            title: null,
            description: null,
            dueDate: null,
            completedAt: null
        );

        $result = $this->repository->getByFiltersAndSort($this->user, $filters);

        $this->assertCount(3, $result);
    }

    /** @test */
    public function it_combines_completed_at_with_other_filters(): void
    {
        $targetDate = Carbon::parse('2024-03-15');

        // Matching task (status=done, completed on target date)
        $matchingTask = Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => StatusEnum::DONE,
            'completed_at' => '2024-03-15 12:00:00'
        ]);

        // Non-matching task (status=todo, no completed_at)
        Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => StatusEnum::TODO,
            'completed_at' => null
        ]);

        // Non-matching task (status=done, but different completion date)
        Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => StatusEnum::DONE,
            'completed_at' => '2024-03-16 12:00:00'
        ]);

        $filters = new TaskFiltersData(
            priority: null,
            status: StatusEnum::DONE,
            title: null,
            description: null,
            dueDate: null,
            completedAt: $targetDate
        );

        $result = $this->repository->getByFiltersAndSort($this->user, $filters);

        $this->assertCount(1, $result);
        $this->assertEquals($matchingTask->id, $result->first()->id);
    }
}
