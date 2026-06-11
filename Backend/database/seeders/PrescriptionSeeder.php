<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Prescription;
use Database\Factories\PrescriptionItemFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class PrescriptionSeeder extends Seeder
{
    public function run(): void
    {
        $patients = Patient::with('user')->get();
        $doctors = Doctor::with('user')->get();

        $totalPrescriptions = 40;
        $daysBack = 30;

        for ($i = 0; $i < $totalPrescriptions; $i++) {
            $doctor = $doctors->random();
            $patient = $patients->random();

            // Spread evenly across the last 30 days
            $daysAgo = (int) floor(($i / $totalPrescriptions) * $daysBack);
            $createdAt = Carbon::now()->subDays($daysAgo)
                ->setTime(rand(8, 20), rand(0, 59), rand(0, 59));

            // ~60% pending, ~40% consumed
            $status = fake()->boolean(40) ? 'consumed' : 'pending';

            $prescription = Prescription::factory()
                ->for($doctor)
                ->for($patient)
                ->has(
                    PrescriptionItemFactory::new()->count(fake()->numberBetween(1, 4)),
                    'items'
                )
                ->create([
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

            if ($status === 'consumed') {
                $consumedAt = (clone $createdAt)->addDays(rand(1, 5));
                $prescription->update([
                    'status' => 'consumed',
                    'consumed_at' => $consumedAt,
                ]);
            }
        }
    }
}
