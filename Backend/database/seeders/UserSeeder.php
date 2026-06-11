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
        $extraDoctors = [
            ['name' => 'Dr. García', 'email' => 'dr.garcia@test.com', 'specialty' => 'Pediatría'],
            ['name' => 'Dra. López', 'email' => 'dra.lopez@test.com', 'specialty' => 'Dermatología'],
            ['name' => 'Dr. Martínez', 'email' => 'dr.martinez@test.com', 'specialty' => 'Traumatología'],
            ['name' => 'Dra. Rodríguez', 'email' => 'dra.rodriguez@test.com', 'specialty' => 'Neurología'],
        ];

        foreach ($extraDoctors as $data) {
            $u = User::factory()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => 'dr123',
            ]);
            $u->syncRoles('doctor');
            Doctor::factory()->create([
                'user_id' => $u->id,
                'specialty' => $data['specialty'],
            ]);
        }

        // Extra patients for prescription variety
        $extraPatients = [
            ['name' => 'Carlos Ruiz', 'email' => 'carlos@test.com', 'birth_date' => '1985-03-22'],
            ['name' => 'María Fernández', 'email' => 'maria@test.com', 'birth_date' => '1992-07-11'],
            ['name' => 'Lucía Gómez', 'email' => 'lucia@test.com', 'birth_date' => '1978-11-30'],
            ['name' => 'Pedro Sánchez', 'email' => 'pedro@test.com', 'birth_date' => '2000-01-15'],
        ];

        foreach ($extraPatients as $data) {
            $u = User::factory()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => 'patient123',
            ]);
            $u->syncRoles('patient');
            Patient::factory()->create([
                'user_id' => $u->id,
                'birth_date' => $data['birth_date'],
            ]);
        }
    }
}
