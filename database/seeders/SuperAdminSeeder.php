<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@telkom.co.id',
            'password' => Hash::make('password123'), // Ganti dengan password yang aman
            'role' => 'superadmin',
            'email_verified_at' => now(),
        ]);
    }
}
