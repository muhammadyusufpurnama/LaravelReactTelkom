<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'email_verified_at' => now(),
            'password' => bcrypt('password'), // password
        ]);

        User::create([
            'name' => 'Admin',
            'email' => 'admin@telkom.co.id',
            'password' => Hash::make('password123'), // Ganti dengan password yang aman
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->call([
            SuperAdminSeeder::class,
            AccountOfficerSeeder::class,
            // Jika punya seeder lain, tambahkan di sini:
            // AnotherTableSeeder::class,
        ]);
    }
}
