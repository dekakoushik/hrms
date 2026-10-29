<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class WebAuthController extends Controller
{
    /**
     * Normalize Indian mobile numbers
     */
    protected function normalizeMobileNumber(string $input): string
    {
        $cleaned = preg_replace('/[^0-9]/', '', $input);

        if (strlen($cleaned) === 12 && str_starts_with($cleaned, '91')) {
            return substr($cleaned, 2);
        }

        if (strlen($cleaned) === 11 && str_starts_with($cleaned, '0')) {
            return substr($cleaned, 1);
        }

        return $cleaned;
    }

    /**
     * Show Superadmin Login Page
     */
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Handle Superadmin Login via Mobile or Email
     */
    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'login.required' => 'Please enter your registered Email address or 10-digit Indian Mobile Number.',
            'password.required' => 'Please enter your password.',
        ]);

        $loginInput = trim($request->input('login'));
        $password = $request->input('password');
        $remember = $request->boolean('remember');

        $user = null;

        if (filter_var($loginInput, FILTER_VALIDATE_EMAIL)) {
            $user = User::where('email', strtolower($loginInput))->first();
        } else {
            $normalized = $this->normalizeMobileNumber($loginInput);
            $user = User::where('mobile_number', $normalized)
                ->orWhere('mobile_number', $loginInput)
                ->first();
        }

        if (! $user || ! Hash::check($password, $user->password)) {
            return back()->withInput($request->only('login', 'remember'))->withErrors([
                'login' => 'Invalid credentials. Please verify your Email or Mobile Number and Password.',
            ]);
        }

        if (! $user->is_active) {
            return back()->withInput($request->only('login'))->withErrors([
                'login' => 'Your account is deactivated. Please contact the administrator.',
            ]);
        }

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'))
            ->with('success', 'Welcome back, ' . $user->name . '!');
    }

    /**
     * Logout Superadmin
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', 'You have been logged out safely.');
    }
}
