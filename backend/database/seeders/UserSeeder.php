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
            'name'  => 'Admin User',
            'email' => 'admin@eyecare.test',
            'phone' => '09171234567',
        ]);

        // ── Staff ─────────────────────────────────────────────────────────────
        User::factory()->staff()->create([
            'name'  => 'Staff User',
            'email' => 'staff@eyecare.test',
            'phone' => '09179876543',
        ]);

        // ── Customers ─────────────────────────────────────────────────────────
        // Primary test customer — used by OrderSeeder and FeedbackSeeder
        User::factory()->customer()->create([
            'name'  => 'Juan Dela Cruz',
            'email' => 'customer@eyecare.test',
            'phone' => '09181234567',
        ]);

        // Second customer for broader coverage (no orders yet — useful for testing
        // the customer list, profile views, and empty-state flows)
        User::factory()->customer()->create([
            'name'  => 'Maria Santos',
            'email' => 'maria@eyecare.test',
            'phone' => '09189876543',
        ]);
    }
}
