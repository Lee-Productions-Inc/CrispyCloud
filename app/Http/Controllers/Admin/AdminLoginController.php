<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminLoginController extends Controller
{
    // Keep the old URL working, but use the single customer-facing sign-in page.
    public function showLoginForm(): RedirectResponse
    {
        return redirect()->route('login');
    }

    // Legacy POST requests use the same authentication and attempt limiting.
    public function login(Request $request): RedirectResponse
    {
        return app(LoginController::class)->login($request);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
