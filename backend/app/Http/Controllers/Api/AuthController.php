<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Handle user login and token generation.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'login' => ['sometimes', 'string'],
            'email' => ['sometimes', 'string'],
            'password' => ['required', 'string'],
        ]);

        $loginIdentifier = trim($request->input('login') ?? $request->input('email') ?? '');

        if (empty($loginIdentifier)) {
            throw ValidationException::withMessages([
                'login' => ['Please enter your Email or Employee ID.'],
            ]);
        }

        // 1. Try finding user by Email
        $user = User::where('email', $loginIdentifier)->first();

        // 2. If not found by email, try finding by Employee ID (e.g. EMP-2026-0001)
        if (!$user) {
            $employee = Employee::where('employee_id', $loginIdentifier)->first();
            if ($employee && $employee->user_id) {
                $user = User::find($employee->user_id);
            }
        }

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['The provided credentials do not match our records.'],
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        if (! $user->is_active) {
            return response()->json([
                'message' => 'Your account is deactivated. Please contact administrator.',
            ], 403);
        }

        // Revoke older tokens for clean session
        $user->tokens()->delete();

        $token = $user->createToken('hrms-auth-token', ['*'], now()->addDays(7))->plainTextToken;

        $employee = Employee::where('user_id', $user->id)->first();

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'employee_id' => $employee?->employee_id ?? null,
            ],
        ]);
    }

    /**
     * Get authenticated user profile.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    /**
     * Logout and revoke tokens.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }
}

