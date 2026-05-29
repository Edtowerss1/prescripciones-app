<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Prescription;
use Database\Factories\PrescriptionItemFactory;
use Illuminate\Database\Seeder;

class PrescriptionSeeder extends Seeder
{
    public function run(): void
    {
        $patient = Patient::whereHas('user', fn ($q) => $q->where('email', 'patient@test.com'))->firstOrFail();
        $doctors = Doctor::with('user')->get();

        $prescriptions = [
            // Pending (4)
            ['doctor_email' => 'dr@test.com', 'status' => 'pending'],
            ['doctor_email' => 'dr@test.com', 'status' => 'pending'],
            ['doctor_email' => 'dr.garcia@test.com', 'status' => 'pending'],
            ['doctor_email' => 'dra.lopez@test.com', 'status' => 'pending'],
            // Consumed (4)
            ['doctor_email' => 'dr@test.com', 'status' => 'consumed'],
            ['doctor_email' => 'dr.garcia@test.com', 'status' => 'consumed'],
            ['doctor_email' => 'dra.lopez@test.com', 'status' => 'consumed'],
            ['doctor_email' => 'dr@test.com', 'status' => 'consumed'],
        ];

        foreach ($prescriptions as $p) {
            $doctor = $doctors->first(fn ($d) => $d->user->email === $p['doctor_email']);

            $factory = Prescription::factory()
                ->for($doctor)
                ->for($patient)
                ->has(PrescriptionItemFactory::new()->count(fake()->numberBetween(2, 3)), 'items');

            if ($p['status'] === 'consumed') {
                $factory = $factory->consumed();
            }

            $factory->create();
        }
    }
}
