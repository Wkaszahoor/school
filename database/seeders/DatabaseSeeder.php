<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'System Administrator', 'email' => 'admin@kort.org.uk',     'password' => 'admin123',     'role' => 'admin'],
            ['name' => 'Dr. Sarah Principal',   'email' => 'principal@kort.org.uk', 'password' => 'principal123', 'role' => 'principal'],
            ['name' => 'Mr. John Teacher',      'email' => 'teacher@kort.org.uk',   'password' => 'teacher123',   'role' => 'teacher'],
            ['name' => 'Ms. Amy Reception',     'email' => 'reception@kort.org.uk', 'password' => 'reception123', 'role' => 'receptionist'],
            ['name' => 'Dr. James Medical',     'email' => 'doctor@kort.org.uk',    'password' => 'doctor123',    'role' => 'doctor'],
            ['name' => 'Mr. Ali Inventory',     'email' => 'inventory@kort.org.uk', 'password' => 'inventory123', 'role' => 'inventory_manager'],
            ['name' => 'Ms. Fatima Helper',     'email' => 'helper@kort.org.uk',    'password' => 'helper123',    'role' => 'principal_helper'],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'name'      => $u['name'],
                    'password'  => $u['password'], // Model mutator will hash it
                    'role'      => $u['role'],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('✓ All demo users created successfully.');
        $this->command->table(
            ['Role', 'Email', 'Password'],
            array_map(fn($u) => [ucwords(str_replace('_', ' ', $u['role'])), $u['email'], $u['password']], $users)
        );

        // Seed Pakistani Education System (Classes 9-12)
        $this->call(PakistaniEducationSystemSeeder::class);
    }
}
