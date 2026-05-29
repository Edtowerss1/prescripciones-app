<?php

namespace Database\Factories;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Prescription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Prescription>
 */
class PrescriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'doctor_id' => Doctor::factory(),
            'patient_id' => Patient::factory(),
            'status' => 'pending',
            'code' => Str::uuid()->toString(),
        ];
    }

    public function consumed(): static
    {
        return $this->state(fn (): array => [
            'status' => 'consumed',
            'consumed_at' => now(),
        ]);
    }
}
