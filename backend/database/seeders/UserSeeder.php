<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@eyecare.test',
            'phone' => '09171234567',
        ]);

        User::factory()->staff()->create([
            'name' => 'Staff User',
            'email' => 'staff@eyecare.test',
            'phone' => '09179876543',
        ]);

        User::factory()->customer()->create([
            'name' => 'Juan Dela Cruz',
            'email' => 'customer@eyecare.test',
            'phone' => '09181234567',
        ]);
    }
}
