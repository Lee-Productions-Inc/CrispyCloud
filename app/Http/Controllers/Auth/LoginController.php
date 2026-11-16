<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Apply the same limit to both /login and legacy /admin/login requests.
        $key = 'crispycloud-login:'.hash(
            'sha256',
            Str::lower($credentials['email']).'|'.$request->ip()
        );

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return back()->withErrors([
                'email' => "Too many sign-in attempts. Try again in {$seconds} seconds.",
            ])->onlyInput('email');
        }

        // Each account type keeps its own database provider and session guard.
        // The admin guard takes precedence if the exact credentials match both.
        if (Auth::guard('admin')->attempt($credentials)) {
            Auth::guard('web')->logout();
            RateLimiter::clear($key);
            $request->session()->regenerate();

            return redirect()->route('admin.dashboard');
        }

        if (Auth::guard('web')->attempt($credentials)) {
            Auth::guard('admin')->logout();
            RateLimiter::clear($key);
            $request->session()->regenerate();

            $destination = $request->session()->pull('crispycloud_after_auth');
            return $destination === 'checkout'
                ? redirect()->route('customer.checkout.index')
                : redirect()->route('customer.menu');
        }

        RateLimiter::hit($key, 60);

        // Do not disclose whether an email belongs to a customer or an admin.
        return back()->withErrors([
            'email' => 'The provided credentials are incorrect.',
        ])->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
