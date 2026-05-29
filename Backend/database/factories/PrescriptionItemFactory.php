<?php

namespace Database\Factories;

use App\Models\Prescription;
use App\Models\PrescriptionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PrescriptionItem>
 */
class PrescriptionItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'prescription_id' => Prescription::factory(),
            'name' => fake()->word(),
            'quantity' => fake()->numberBetween(1, 100),
            'dosage' => fake()->randomElement(['10mg', '25mg', '50mg', '100mg', '500mg']),
            'instructions' => fake()->sentence(),
        ];
    }
}
