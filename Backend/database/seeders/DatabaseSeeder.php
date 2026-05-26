<?php

namespace Database\Seeders;

use App\Models\User;
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
        foreach (['admin', 'doctor', 'patient'] as $roleName) {
            Role::findOrCreate($roleName, 'api');
        }

        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => 'admin123',
        ])->assignRole('admin');

        User::factory()->create([
            'name' => 'Doctor',
            'email' => 'dr@test.com',
            'password' => 'dr123',
        ])->assignRole('doctor');

        User::factory()->create([
            'name' => 'Patient',
            'email' => 'patient@test.com',
            'password' => 'patient123',
        ])->assignRole('patient');
    }
}
