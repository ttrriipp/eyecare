<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ── Admin ─────────────────────────────────────────────────────────────
        User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@eyecare.test',
            'phone' => '09171234567',
            'date_of_birth' => null,
            'address' => null,
            'customer_notes' => null,
        ]);

        // ── Staff ─────────────────────────────────────────────────────────────
        User::factory()->staff()->create([
            'name' => 'Staff User',
            'email' => 'staff@eyecare.test',
            'phone' => '09179876543',
            'date_of_birth' => null,
            'address' => null,
            'customer_notes' => null,
        ]);

        // ── Customers ─────────────────────────────────────────────────────────
        // Primary test customer — used by OrderSeeder and FeedbackSeeder
        User::factory()->customer()->create([
            'name' => 'Juan Dela Cruz',
            'email' => 'customer@eyecare.test',
            'phone' => '09181234567',
            'date_of_birth' => '1990-05-12',
            'address' => "123 Timog Avenue, Brgy. South Triangle\nQuezon City, Metro Manila",
            'customer_notes' => 'Prefers lightweight frames. SC/PWD discount verified on file (manual). Reminder: sensitive to tight nose pads.',
        ]);

        // Second customer for broader coverage (list/profile / empty-state flows)
        User::factory()->customer()->create([
            'name' => 'Maria Santos',
            'email' => 'maria@eyecare.test',
            'phone' => '09189876543',
            'date_of_birth' => '1988-03-22',
            'address' => '456 Session Road, Baguio City, Benguet',
            'customer_notes' => null,
        ]);
    }
}
