<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Normalize Indian mobile numbers to standard 10 digits
     */
    protected function normalizeMobileNumber(string $input): string
    {
        $cleaned = preg_replace('/[^0-9]/', '', $input);
        
        // If 12 digits starting with 91 (e.g. 919876543210)
        if (strlen($cleaned) === 12 && str_starts_with($cleaned, '91')) {
            return substr($cleaned, 2);
        }
        
        // If 11 digits starting with 0 (e.g. 09876543210)
        if (strlen($cleaned) === 11 && str_starts_with($cleaned, '0')) {
            return substr($cleaned, 1);
        }
        
        return $cleaned;
    }

    /**
     * Handle Superadmin & Staff Login via Email OR Mobile Number
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string'],
        ]);

        $loginInput = trim($request->input('login'));
        $password = $request->input('password');
        $deviceName = $request->input('device_name', 'mobile_app');

        $user = null;

        // Check if identifier is an email
        if (filter_var($loginInput, FILTER_VALIDATE_EMAIL)) {
            $user = User::where('email', strtolower($loginInput))->first();
        } else {
            // Treat as mobile number - try direct and normalized
            $normalized = $this->normalizeMobileNumber($loginInput);
            $user = User::where('mobile_number', $normalized)
                ->orWhere('mobile_number', $loginInput)
                ->first();
        }

        // Verify credentials
        if (! $user || ! Hash::check($password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials. Please verify your email/mobile number and password.',
                'errors' => [
                    'login' => ['The provided credentials do not match our records.'],
                ],
            ], 422);
        }

        // Check active status
        if (! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been deactivated. Please contact the administrator.',
            ], 403);
        }

        // Create Sanctum Token
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'token' => $token,
            'user' => $user,
        ]);
    }

    /**
     * Get authenticated user profile
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'user' => $request->user(),
        ]);
    }

    /**
     * Update Superadmin / User Profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'mobile_number' => ['required', 'string', 'max:20', 'unique:users,mobile_number,' . $user->id],
            'designation' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'pincode' => ['nullable', 'string', 'max:20'],
            'avatar' => ['nullable'], // Can be uploaded file or base64 data URI
        ]);

        // Normalize mobile number
        $validated['mobile_number'] = $this->normalizeMobileNumber($validated['mobile_number']);

        // Handle Avatar File Upload if present
        if ($request->hasFile('avatar')) {
            $request->validate([
                'avatar' => ['image', 'mimes:jpeg,png,jpg,webp,svg', 'max:4096'],
            ]);

            // Delete old avatar if exists
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            $path = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar'] = $path;
        } elseif ($request->filled('avatar_base64')) {
            // Handle base64 avatar from mobile app or webcam
            $base64Data = $request->input('avatar_base64');
            if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
                $data = substr($base64Data, strpos($base64Data, ',') + 1);
                $type = strtolower($type[1]);
                $decoded = base64_decode($data);

                if ($decoded !== false) {
                    $filename = 'avatars/' . Str::uuid() . '.' . ($type === 'jpeg' ? 'jpg' : $type);
                    Storage::disk('public')->put($filename, $decoded);
                    $validated['avatar'] = $filename;
                }
            }
        } else {
            unset($validated['avatar']);
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'user' => $user->fresh(),
        ]);
    }

    /**
     * Update Password
     */
    public function changePassword(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($request->input('current_password'), $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Your current password is incorrect.',
                'errors' => [
                    'current_password' => ['The provided current password does not match our records.'],
                ],
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->input('password')),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully',
        ]);
    }

    /**
     * Revoke access token / Logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out',
        ]);
    }
}
