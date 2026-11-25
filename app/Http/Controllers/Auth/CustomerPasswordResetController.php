<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CustomerResetEmail;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;
use Throwable;

class CustomerPasswordResetController extends Controller
{
    private function ensureAvailable(): void
    {
        abort_unless(config('services.brevo.api_key') && config('services.brevo.sender_email'), 503);
    }

    public function requestForm(): View
    {
        $this->ensureAvailable();
        return view('auth.forgot-password');
    }

    public function sendLink(Request $request, CustomerResetEmail $mailer): RedirectResponse
    {
        $this->ensureAvailable();
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        try {
            Password::broker('users')->sendResetLink($data, function (User $user, string $token) use ($mailer) {
                $mailer->send($user, $token);
            });
        } catch (Throwable $error) {
            // Do not log the token or the email link.
            logger()->warning('Customer reset email delivery failed', ['exception_class' => get_class($error)]);
            return back()->withErrors(['email' => 'Unable to send reset emails at the moment. Try again later.']);
        }

        // Do not disclose whether this email belongs to a registered customer.
        return back()->with('status', 'If that email is registered, a password reset link will be sent. Please check your inbox and spam folder.');
    }

    public function resetForm(Request $request, string $token): View
    {
        $this->ensureAvailable();
        return view('auth.reset-password', ['token' => $token, 'email' => (string) $request->query('email', '')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->ensureAvailable();
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::broker('users')->reset($data, function (User $user, string $password) {
            $user->forceFill(['password_hash' => Hash::make($password)])->save();
            event(new PasswordReset($user));
        });

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'Your password has been updated. You can now sign in.');
        }

        return back()->withInput($request->only('email'))
            ->withErrors(['email' => 'This reset link is invalid or has expired. Please request a new one.']);
    }
}
