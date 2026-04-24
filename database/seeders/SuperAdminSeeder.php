<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => env('SUPER_ADMIN_EMAIL', 'superadmin@vetfollow.com')],
            [
                'name' => env('SUPER_ADMIN_NAME', 'Super Admin'),
                'password' => env('SUPER_ADMIN_PASSWORD', 'change-me'),
                'is_super_admin' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
