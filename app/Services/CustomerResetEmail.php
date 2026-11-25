<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CustomerResetEmail
{
    public function send(User $user, string $token): void
    {
        $key = config('services.brevo.api_key');
        $sender = config('services.brevo.sender_email');
        $name = config('services.brevo.sender_name', "Chick's Pops");
        if (!$key || !$sender) {
            throw new RuntimeException('Password reset email is not configured.');
        }

        // Use the configured public app host, never an arbitrary Host header.
        $host = rtrim((string) config('app.url'), '/');
        if (!str_starts_with($host, 'https://') && app()->environment('production')) {
            throw new RuntimeException('APP_URL must be HTTPS for production password resets.');
        }
        $link = $host.'/reset-password/'.rawurlencode($token).'?email='.rawurlencode($user->email);
        $message = "You requested a password reset for your Chick's Pops account.\n\n".
            "Reset password: {$link}\n\n".
            "This link expires after 60 minutes. If you did not request this, ignore this email.";

        $response = Http::withHeaders([
                'api-key' => $key,
                'accept' => 'application/json',
            ])->timeout(10)->post('https://api.brevo.com/v3/smtp/email', [
                'sender' => ['name' => $name, 'email' => $sender],
                'to' => [['email' => $user->email, 'name' => $user->full_name]],
                'subject' => "Chick's Pops password reset",
                'textContent' => $message,
            ]);

        // Do not log API responses here: they can contain email addresses.
        if (!$response->successful()) {
            throw new RuntimeException('The email provider rejected the password reset request.');
        }
    }
}
