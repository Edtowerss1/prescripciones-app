<?php

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// --------------------------------------------------------------------
// Create validation: StorePrescriptionRequest rules
// --------------------------------------------------------------------

test('store_validates_missing_patient_id_returns_422', function () {
    $doctorUser = User::factory()->doctor()->create();
    $doctor = Doctor::factory()->create(['user_id' => $doctorUser->id]);

    $token = $doctorUser->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/prescriptions', [
        'items' => [['name' => 'Amoxicilina', 'quantity' => 1]],
    ]);

    $response->assertUnprocessable()
        ->assertJsonStructure(['message', 'code', 'details']);
});

test('store_validates_invalid_patient_id_returns_422', function () {
    $doctorUser = User::factory()->doctor()->create();
    $doctor = Doctor::factory()->create(['user_id' => $doctorUser->id]);

    $token = $doctorUser->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/prescriptions', [
        'patient_id' => 99999,
        'items' => [['name' => 'Amoxicilina', 'quantity' => 1]],
    ]);

    $response->assertUnprocessable()
        ->assertJsonStructure(['message', 'code', 'details']);
});

test('store_validates_empty_items_returns_422', function () {
    $doctorUser = User::factory()->doctor()->create();
    $doctor = Doctor::factory()->create(['user_id' => $doctorUser->id]);
    $patientUser = User::factory()->patient()->create();
    $patient = Patient::factory()->create(['user_id' => $patientUser->id]);

    $token = $doctorUser->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/prescriptions', [
        'patient_id' => $patient->id,
        'items' => [],
    ]);

    $response->assertUnprocessable()
        ->assertJsonStructure(['message', 'code', 'details']);
});

test('store_validates_missing_item_name_returns_422', function () {
    $doctorUser = User::factory()->doctor()->create();
    $doctor = Doctor::factory()->create(['user_id' => $doctorUser->id]);
    $patientUser = User::factory()->patient()->create();
    $patient = Patient::factory()->create(['user_id' => $patientUser->id]);

    $token = $doctorUser->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/prescriptions', [
        'patient_id' => $patient->id,
        'items' => [['quantity' => 1]],
    ]);

    $response->assertUnprocessable()
        ->assertJsonStructure(['message', 'code', 'details']);
});

test('store_validates_negative_quantity_returns_422', function () {
    $doctorUser = User::factory()->doctor()->create();
    $doctor = Doctor::factory()->create(['user_id' => $doctorUser->id]);
    $patientUser = User::factory()->patient()->create();
    $patient = Patient::factory()->create(['user_id' => $patientUser->id]);

    $token = $doctorUser->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/prescriptions', [
        'patient_id' => $patient->id,
        'items' => [['name' => 'Amoxicilina', 'quantity' => 0]],
    ]);

    $response->assertUnprocessable()
        ->assertJsonStructure(['message', 'code', 'details']);
});

// --------------------------------------------------------------------
// Filter validation: PrescriptionFilterRequest rules
// --------------------------------------------------------------------

test('filter_validates_invalid_status_returns_422', function () {
    $doctorUser = User::factory()->doctor()->create();
    $doctor = Doctor::factory()->create(['user_id' => $doctorUser->id]);

    $token = $doctorUser->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/prescriptions?status=xxx');

    $response->assertUnprocessable()
        ->assertJsonStructure(['message', 'code', 'details']);
});

test('filter_validates_invalid_date_format_returns_422', function () {
    $doctorUser = User::factory()->doctor()->create();
    $doctor = Doctor::factory()->create(['user_id' => $doctorUser->id]);

    $token = $doctorUser->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/prescriptions?from=abc');

    $response->assertUnprocessable()
        ->assertJsonStructure(['message', 'code', 'details']);
});

test('filter_validates_to_before_from_returns_422', function () {
    $doctorUser = User::factory()->doctor()->create();
    $doctor = Doctor::factory()->create(['user_id' => $doctorUser->id]);

    $token = $doctorUser->createToken('test')->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/prescriptions?from=2026-06-01&to=2026-01-01');

    $response->assertUnprocessable()
        ->assertJsonStructure(['message', 'code', 'details']);
});
