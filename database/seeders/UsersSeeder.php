<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::firstOrCreate([
            'name' => 'Belmar Alberto',
            'email' => 'webmaster@dispersion.com.mx',
            'email_verified_at' => now(),
            'password' => 'gH@5QqL&6PEe&F3yK8%@qHb4',
            'created_at' => now(),
        ]);
    }
}
