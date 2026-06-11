<?php

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// --------------------------------------------------------------------
// Create — only doctors may create prescriptions
// --------------------------------------------------------------------

test('patient_cannot_create_prescription_returns_403', function () {
    $patientUser = User::factory()->patient()->create();
    Patient::factory()->create(['user_id' => $patientUser->id]);

    $token = $patientUser->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/prescriptions', [
        'patient_id' => 1,
        'items' => [['name' => 'Ibuprofeno', 'quantity' => 10]],
    ]);

    $response->assertForbidden()
        ->assertJsonStructure(['message', 'code', 'details']);
});

test('patient_cannot_access_doctor_prescription_list_returns_403', function () {
    $patientUser = User::factory()->patient()->create();
    Patient::factory()->create(['user_id' => $patientUser->id]);

    $token = $patientUser->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/prescriptions');

    $response->assertForbidden()
        ->assertJsonStructure(['message', 'code', 'details']);
});

// --------------------------------------------------------------------
// Detail / Consume — non-owners receive 403
// --------------------------------------------------------------------

test('non_owner_doctor_cannot_view_prescription_detail_returns_403', function () {
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

    $response->assertForbidden()
        ->assertJsonStructure(['message', 'code', 'details']);
});

test('non_owner_patient_cannot_consume_prescription_returns_403', function () {
    $doctorUser = User::factory()->doctor()->create();
    $doctor = Doctor::factory()->create(['user_id' => $doctorUser->id]);
    $patientAUser = User::factory()->patient()->create();
    $patientA = Patient::factory()->create(['user_id' => $patientAUser->id]);
    $patientBUser = User::factory()->patient()->create();
    $patientB = Patient::factory()->create(['user_id' => $patientBUser->id]);

    $prescription = Prescription::factory()
        ->for($doctor)
        ->for($patientA)
        ->create(['status' => 'pending']);

    $token = $patientBUser->createToken('test')->plainTextToken;

    $response = $this->withToken($token)
        ->putJson("/api/prescriptions/{$prescription->id}/consume");

    $response->assertForbidden()
        ->assertJsonStructure(['message', 'code', 'details']);
});

// --------------------------------------------------------------------
// my-prescriptions — requires patient role
// --------------------------------------------------------------------

test('non_patient_cannot_access_my_prescriptions_returns_403', function () {
    $doctorUser = User::factory()->doctor()->create();
    Doctor::factory()->create(['user_id' => $doctorUser->id]);

    $token = $doctorUser->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/me/prescriptions');

    $response->assertForbidden()
        ->assertJsonStructure(['message', 'code', 'details']);
});

// --------------------------------------------------------------------
// Admin — can view any prescription detail
// --------------------------------------------------------------------

test('admin_can_view_any_prescription_detail_returns_200', function () {
    $doctorUser = User::factory()->doctor()->create();
    $doctor = Doctor::factory()->create(['user_id' => $doctorUser->id]);
    $patientUser = User::factory()->patient()->create();
    $patient = Patient::factory()->create(['user_id' => $patientUser->id]);
    $adminUser = User::factory()->admin()->create();

    $prescription = Prescription::factory()
        ->for($doctor)
        ->for($patient)
        ->create();

    $token = $adminUser->createToken('test')->plainTextToken;

    $response = $this->withToken($token)
        ->getJson("/api/prescriptions/{$prescription->id}");

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'id',
                'code',
                'status',
                'doctor' => ['id', 'name'],
                'patient' => ['id', 'name'],
                'items',
                'created_at',
            ],
        ]);
});
