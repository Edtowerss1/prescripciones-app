<?php

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// --------------------------------------------------------------------
// Prescription Lifecycle — Smoke Tests (full test suite → Batch 8)
// --------------------------------------------------------------------

test('doctor can create prescription with items and receive 201', function () {
    $doctorUser = User::factory()->doctor()->create();
    $doctor = Doctor::factory()->create(['user_id' => $doctorUser->id]);
    $patientUser = User::factory()->patient()->create();
    $patient = Patient::factory()->create(['user_id' => $patientUser->id]);

    $token = $doctorUser->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/prescriptions', [
        'patient_id' => $patient->id,
        'notes' => 'Tomar con agua',
        'items' => [
            [
                'name' => 'Amoxicilina 500mg',
                'dosage' => '1 cada 8 horas',
                'quantity' => 15,
                'instructions' => 'Después de comer',
            ],
        ],
    ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'id',
                'code',
                'status',
                'notes',
                'consumed_at',
                'doctor' => ['id', 'name'],
                'patient' => ['id', 'name'],
                'items' => [
                    ['id', 'name', 'dosage', 'quantity', 'instructions'],
                ],
                'created_at',
            ],
        ]);

    expect($response->json('data.status'))->toBe('pending');
    expect($response->json('data.notes'))->toBe('Tomar con agua');
    expect(Prescription::count())->toBe(1);
    expect(PrescriptionItem::count())->toBe(1);
});

test('doctor can view own prescription detail', function () {
    $doctorUser = User::factory()->doctor()->create();
    $doctor = Doctor::factory()->create(['user_id' => $doctorUser->id]);
    $patientUser = User::factory()->patient()->create();
    $patient = Patient::factory()->create(['user_id' => $patientUser->id]);

    $prescription = Prescription::factory()
        ->for($doctor)
        ->for($patient)
        ->create(['notes' => 'Detalle de prueba']);

    PrescriptionItem::factory()
        ->for($prescription)
        ->create([
            'name' => 'Ibuprofeno',
            'dosage' => '1 cada 6 horas',
            'quantity' => 10,
            'instructions' => 'Con comida',
        ]);

    $token = $doctorUser->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->getJson("/api/prescriptions/{$prescription->id}");

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'id',
                'code',
                'status',
                'notes',
                'doctor' => ['id', 'name'],
                'patient' => ['id', 'name'],
                'items' => [
                    ['id', 'name', 'dosage', 'quantity', 'instructions'],
                ],
                'created_at',
            ],
        ]);

    expect($response->json('data.status'))->toBe('pending');
    expect($response->json('data.notes'))->toBe('Detalle de prueba');
});

test('patient can consume pending prescription', function () {
    $doctorUser = User::factory()->doctor()->create();
    $doctor = Doctor::factory()->create(['user_id' => $doctorUser->id]);
    $patientUser = User::factory()->patient()->create();
    $patient = Patient::factory()->create(['user_id' => $patientUser->id]);

    $prescription = Prescription::factory()
        ->for($doctor)
        ->for($patient)
        ->create(['status' => 'pending']);

    $token = $patientUser->createToken('test')->plainTextToken;

    $response = $this->withToken($token)
        ->putJson("/api/prescriptions/{$prescription->id}/consume");

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => ['consumed_at'],
        ]);

    expect($response->json('data.status'))->toBe('consumed');
    expect($response->json('data.consumed_at'))->not->toBeNull();

    $prescription->refresh();
    expect($prescription->status)->toBe('consumed');
    expect($prescription->consumed_at)->not->toBeNull();
});

test('non-owner doctor receives 404 on prescription detail', function () {
    $doctorAUser = User::factory()->doctor()->create();
    $doctorA = Doctor::factory()->create(['user_id' => $doctorAUser->id]);
    $doctorBUser = User::factory()->doctor()->create();
    Doctor::factory()->create(['user_id' => $doctorBUser->id]);
    $patientUser = User::factory()->patient()->create();
    $patient = Patient::factory()->create(['user_id' => $patientUser->id]);

    $prescription = Prescription::factory()
        ->for($doctorA)
        ->for($patient)
        ->create();

    $token = $doctorBUser->createToken('test')->plainTextToken;

    $response = $this->withToken($token)
        ->getJson("/api/prescriptions/{$prescription->id}");

    $response->assertNotFound();
});
