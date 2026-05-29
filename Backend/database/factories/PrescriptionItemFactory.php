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
            'name' => fake()->randomElement([
                'Ibuprofeno 600mg',
                'Amoxicilina 500mg',
                'Omeprazol 20mg',
                'Losartán 50mg',
                'Metformina 850mg',
                'Atorvastatina 20mg',
                'Levotiroxina 100mcg',
                'Salbutamol 100mcg',
                'Enalapril 10mg',
                'Paracetamol 500mg',
            ]),
            'quantity' => fake()->numberBetween(1, 100),
            'dosage' => fake()->randomElement(['10mg', '25mg', '50mg', '100mg', '500mg']),
            'instructions' => fake()->sentence(),
        ];
    }
}
