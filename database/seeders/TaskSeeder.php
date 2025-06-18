<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::factory(10)->create();

        foreach ($users as $user) {
            $parentTasks = Task::factory()
                ->count(rand(10, 15))
                ->for($user)
                ->create();

            foreach ($parentTasks as $parentTask) {
                Task::factory()
                    ->count(rand(8, 12))
                    ->for($user)
                    ->create([
                        'parent_id' => $parentTask->id,
                    ]);
            }
        }
    }
}
