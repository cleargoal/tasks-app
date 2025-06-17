<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PriorityEnum;
use App\Enums\StatusEnum;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        // Created in the past
        $createdAt = $this->faker->dateTimeBetween('-90 days', '-1 day');

        // Status: todo or done
        $status = $this->faker->randomElement([StatusEnum::TODO, StatusEnum::DONE]);

        // Due date: optional, 1–60 days after created_at
        $dueDate = $this->faker->optional()->dateTimeBetween(
            $createdAt->format('Y-m-d') . ' +1 day',
            $createdAt->format('Y-m-d') . ' +60 days'
        );

        // Completed at: only if done
        $completedAt = null;
        if ($status === StatusEnum::DONE) {
            // The latest it can be is either due_date or now
            $latestCompletion = $dueDate && $dueDate < now() ? $dueDate : now();

            $completedAt = $this->faker->dateTimeBetween($createdAt, $latestCompletion);
        }

        return [
            'user_id'      => User::factory(),
            'parent_id'    => null,
            'title'        => $this->faker->sentence,
            'description'  => $this->faker->paragraph,
            'status'       => $status->value,
            'priority'     => $this->faker->randomElement(PriorityEnum::cases())->value,
            'due_date'     => $dueDate,
            'completed_at' => $completedAt,
            'created_at'   => $createdAt,
            'updated_at'   => $createdAt,
        ];
    }
}
