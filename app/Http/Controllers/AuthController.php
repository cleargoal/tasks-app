<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController
{
    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $data = $request->toData();

            $user = User::create([
                'name' => $data->name,
                'email' => $data->email,
                'password' => Hash::make($data->password),
            ]);

            return response()->json(['message' => 'User registered successfully', 'user' => $user], Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Registration failed'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Log in user and create token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $data = $request->toData();

            $user = User::where('email', $data->email)->first();

            if (! $user || ! Hash::check($data->password, $user->password)) {
                return response()->json([
                    'message' => 'The provided credentials are incorrect.',
                ], Response::HTTP_UNAUTHORIZED);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json(['access_token' => $token, 'token_type' => 'Bearer'], Response::HTTP_OK);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Login failed'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Logout user (revoke the token).
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json(['message' => 'Logged out successfully'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Logout failed'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get current authenticated user details.
     */
    public function me(Request $request): JsonResponse
    {
        try {
            return response()->json($request->user(), Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve user details'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
