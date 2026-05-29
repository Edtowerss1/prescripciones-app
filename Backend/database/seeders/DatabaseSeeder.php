<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Roles
        foreach (['admin', 'doctor', 'patient'] as $roleName) {
            Role::findOrCreate($roleName, 'api');
        }

        // Users + profiles
        $this->call(UserSeeder::class);

        // Prescriptions
        $this->call(PrescriptionSeeder::class);
    }
}
