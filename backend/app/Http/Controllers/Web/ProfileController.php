<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Show Profile under Settings / My Account
     */
    public function show(): View
    {
        $user = Auth::user();
        $isAdmin = in_array($user->role, ['superadmin', 'admin']);
        $employee = \App\Models\Employee::with(['department', 'designation', 'company', 'shift', 'location', 'manager'])
            ->where('user_id', $user->id)
            ->first();

        return view('settings.profile', compact('user', 'isAdmin', 'employee'));
    }

    /**
     * Update Superadmin Profile Details (Admins only)
     */
    public function update(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $isAdmin = in_array($user->role, ['superadmin', 'admin']);

        if (!$isAdmin) {
            return back()->withErrors([
                'profile_error' => 'You have view-only access to your employee profile. Contact Superadmin/HR to request modifications.',
            ]);
        }

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
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:4096'],
        ], [
            'mobile_number.unique' => 'This mobile number is already in use by another user account.',
            'email.unique' => 'This email address is already in use.',
        ]);

        // Clean Indian Mobile number
        $cleanedMobile = preg_replace('/[^0-9]/', '', $validated['mobile_number']);
        if (strlen($cleanedMobile) === 12 && str_starts_with($cleanedMobile, '91')) {
            $cleanedMobile = substr($cleanedMobile, 2);
        } elseif (strlen($cleanedMobile) === 11 && str_starts_with($cleanedMobile, '0')) {
            $cleanedMobile = substr($cleanedMobile, 1);
        }
        $validated['mobile_number'] = $cleanedMobile;

        // Avatar upload handling
        if ($request->hasFile('avatar')) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            $path = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar'] = $path;
        } else {
            unset($validated['avatar']);
        }

        $user->update($validated);

        return back()->with('success', 'Profile details updated successfully!');
    }

    /**
     * Update Superadmin Password
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.confirmed' => 'New password confirmation does not match.',
            'password.min' => 'Password must be at least 8 characters long.',
        ]);

        if (! Hash::check($request->input('current_password'), $user->password)) {
            return back()->withErrors([
                'current_password' => 'The provided current password does not match your active password.',
            ]);
        }

        $user->update([
            'password' => Hash::make($request->input('password')),
        ]);

        return back()->with('success', 'Your password has been changed successfully!');
    }
}
