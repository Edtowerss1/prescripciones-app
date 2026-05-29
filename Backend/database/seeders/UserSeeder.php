<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin — no profile
        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => 'admin123',
        ]);
        $admin->syncRoles('admin');

        // Doctor — with Doctor profile
        $doctor = User::factory()->create([
            'name' => 'Doctor',
            'email' => 'dr@test.com',
            'password' => 'dr123',
        ]);
        $doctor->syncRoles('doctor');
        Doctor::factory()->create([
            'user_id' => $doctor->id,
            'specialty' => 'Cardiología',
            'license_number' => 'LIC-7845',
        ]);

        // Patient — with Patient profile
        $patient = User::factory()->create([
            'name' => 'Patient',
            'email' => 'patient@test.com',
            'password' => 'patient123',
        ]);
        $patient->syncRoles('patient');
        Patient::factory()->create([
            'user_id' => $patient->id,
            'birth_date' => '1990-05-15',
        ]);

        // Extra doctors for prescription variety
        $extra1 = User::factory()->create(['name' => 'Dr. García', 'email' => 'dr.garcia@test.com', 'password' => 'dr123']);
        $extra1->syncRoles('doctor');
        Doctor::factory()->create(['user_id' => $extra1->id]);

        $extra2 = User::factory()->create(['name' => 'Dra. López', 'email' => 'dra.lopez@test.com', 'password' => 'dr123']);
        $extra2->syncRoles('doctor');
        Doctor::factory()->create(['user_id' => $extra2->id]);
    }
}
