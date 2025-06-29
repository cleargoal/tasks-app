<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest as RegisterUserRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class RequestValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_request_validation_passes_with_valid_data(): void
    {
        $request = new LoginRequest();
        $data = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_login_request_validation_fails_without_email(): void
    {
        $request = new LoginRequest();
        $data = [
            'password' => 'password123',
        ];

        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    public function test_login_request_validation_fails_with_invalid_email(): void
    {
        $request = new LoginRequest();
        $data = [
            'email' => 'invalid-email',
            'password' => 'password123',
        ];

        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    public function test_login_request_validation_fails_without_password(): void
    {
        $request = new LoginRequest();
        $data = [
            'email' => 'test@example.com',
        ];

        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    public function test_register_request_validation_passes_with_valid_data(): void
    {
        $request = new RegisterUserRequest();
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
        ];

        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_register_request_validation_fails_without_name(): void
    {
        $request = new RegisterUserRequest();
        $data = [
            'email' => 'john@example.com',
            'password' => 'password123',
        ];

        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_register_request_validation_fails_with_too_long_name(): void
    {
        $request = new RegisterUserRequest();
        $data = [
            'name' => str_repeat('a', 256),
            'email' => 'john@example.com',
            'password' => 'password123',
        ];

        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_register_request_validation_fails_with_invalid_email(): void
    {
        $request = new RegisterUserRequest();
        $data = [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'password' => 'password123',
        ];

        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    public function test_register_request_validation_fails_with_short_password(): void
    {
        $request = new RegisterUserRequest();
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => '1234567',
        ];

        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }
}
