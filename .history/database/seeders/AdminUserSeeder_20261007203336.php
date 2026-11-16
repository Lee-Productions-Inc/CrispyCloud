<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        AdminUser::updateOrCreate(
            ['email' => 'admin@crispycloud.test'],
            [
                'full_name' => 'CrispyCloud Administrator',         // Set the full name for the admin user
                'password_hash' => Hash::make('crispycloud@Admin123'), // Set the password for the admin user
                'role' => 'Admin',
            ]
        );
    }
}