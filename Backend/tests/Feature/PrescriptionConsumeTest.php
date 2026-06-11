<?php

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// --------------------------------------------------------------------
// Consume prescription: pending → consumed, already consumed → 409
// --------------------------------------------------------------------

test('patient_consumes_pending_prescription_returns_200', function () {
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
            'consumed_at',
        ]);

    expect($response->json('status'))->toBe('consumed');
    expect($response->json('consumed_at'))->not->toBeNull();

    $prescription->refresh();
    expect($prescription->status)->toBe('consumed');
    expect($prescription->consumed_at)->not->toBeNull();
});

test('already_consumed_prescription_returns_409', function () {
    $doctorUser = User::factory()->doctor()->create();
    $doctor = Doctor::factory()->create(['user_id' => $doctorUser->id]);
    $patientUser = User::factory()->patient()->create();
    $patient = Patient::factory()->create(['user_id' => $patientUser->id]);

    $prescription = Prescription::factory()
        ->for($doctor)
        ->for($patient)
        ->consumed()
        ->create();

    $token = $patientUser->createToken('test')->plainTextToken;

    $response = $this->withToken($token)
        ->putJson("/api/prescriptions/{$prescription->id}/consume");

    $response->assertStatus(409)
        ->assertJsonStructure(['message', 'code', 'details'])
        ->assertJson(['code' => 'CONFLICT']);
});
