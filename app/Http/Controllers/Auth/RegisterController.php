<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone_number' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'terms' => ['accepted'],
        ]);

        $user = User::create([
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'phone_number' => $validated['phone_number'] ?? null,
            'password_hash' => Hash::make($validated['password']),
            'terms_accepted_at' => now(),
            'terms_version' => config('commerce_legal.version'),
            'privacy_notice_version' => config('commerce_legal.version'),
        ]);

        Auth::guard('web')->login($user);

        $request->session()->regenerate();

        $destination = $request->session()->pull('crispycloud_after_auth');
        return $destination === 'checkout'
            ? redirect()->route('customer.checkout.index')
            : redirect()->route('customer.menu');
    }
}