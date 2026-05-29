<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Prescription;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

class PrescriptionService
{
    /**
     * Create a prescription with items atomically.
     *
     * @param  array<int, array{name: string, quantity: int, dosage?: ?string, instructions?: ?string}>  $items
     */
    public function createPrescription(
        Doctor $doctor,
        Patient $patient,
        ?string $notes,
        array $items,
    ): Prescription {
        return DB::transaction(function () use ($doctor, $patient, $notes, $items): Prescription {
            $prescription = Prescription::create([
                'doctor_id' => $doctor->id,
                'patient_id' => $patient->id,
                'notes' => $notes,
                'status' => 'pending',
            ]);

            foreach ($items as $item) {
                $prescription->items()->create([
                    'name' => $item['name'],
                    'quantity' => $item['quantity'] ?? 1,
                    'dosage' => $item['dosage'] ?? null,
                    'instructions' => $item['instructions'] ?? null,
                ]);
            }

            return $prescription->load('items');
        });
    }

    /**
     * Mark a prescription as consumed.
     *
     * @throws HttpResponseException
     */
    public function consumePrescription(Prescription $prescription): Prescription
    {
        if ($prescription->status !== 'pending') {
            throw new HttpResponseException(
                response()->json(['message' => 'Prescription is already consumed'], 409)
            );
        }

        $prescription->update([
            'status' => 'consumed',
            'consumed_at' => now(),
        ]);

        return $prescription;
    }
}
