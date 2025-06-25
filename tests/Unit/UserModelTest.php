<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\HasApiTokens;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_many_tasks(): void
    {
        $user = User::factory()->create();
        $task1 = Task::factory()->create(['user_id' => $user->id]);
        $task2 = Task::factory()->create(['user_id' => $user->id]);

        $tasks = $user->tasks;
        $this->assertCount(2, $tasks);
        $this->assertTrue($tasks->contains($task1));
        $this->assertTrue($tasks->contains($task2));
    }

    public function test_user_has_fillable_attributes(): void
    {
        $user = new User();
        $expected = ['name', 'email', 'password'];

        $this->assertEquals($expected, $user->getFillable());
    }

    public function test_user_has_hidden_attributes(): void
    {
        $user = new User();
        $expected = ['password', 'remember_token'];

        $this->assertEquals($expected, $user->getHidden());
    }

    public function test_user_uses_has_api_tokens_trait(): void
    {
        $user = new User();
        $traits = class_uses_recursive(get_class($user));

        $this->assertContains(HasApiTokens::class, $traits);
    }

    public function test_user_factory_creates_valid_user(): void
    {
        $user = User::factory()->create();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => $user->email,
        ]);

        $this->assertNotEmpty($user->name);
        $this->assertNotEmpty($user->email);
        $this->assertNotEmpty($user->password);
    }

    public function test_user_email_is_cast_to_datetime(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $user->email_verified_at);
    }

    public function test_user_password_is_hashed(): void
    {
        $user = User::factory()->create();

        $this->assertNotEquals('password', $user->password);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('password', $user->password));
    }
}
