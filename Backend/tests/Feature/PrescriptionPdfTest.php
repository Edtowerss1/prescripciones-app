<?php

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// --------------------------------------------------------------------
// Prescription PDF Download — Batch 5
// --------------------------------------------------------------------

test('doctor owner can download prescription as PDF', function () {
    $doctorUser = User::factory()->doctor()->create();
    $doctor = Doctor::factory()->create(['user_id' => $doctorUser->id]);
    $patientUser = User::factory()->patient()->create();
    $patient = Patient::factory()->create(['user_id' => $patientUser->id]);

    $prescription = Prescription::factory()
        ->for($doctor)
        ->for($patient)
        ->create();

    PrescriptionItem::factory()
        ->for($prescription)
        ->create();

    $token = $doctorUser->createToken('test')->plainTextToken;

    $response = $this->withToken($token)
        ->get("/api/prescriptions/{$prescription->id}/pdf");

    $response->assertSuccessful();
    $response->assertHeader('Content-Type', 'application/pdf');
    $response->assertHeader('Content-Disposition', 'attachment; filename=prescription-'.$prescription->code.'.pdf');
});

test('patient owner can download prescription as PDF', function () {
    $doctorUser = User::factory()->doctor()->create();
    $doctor = Doctor::factory()->create(['user_id' => $doctorUser->id]);
    $patientUser = User::factory()->patient()->create();
    $patient = Patient::factory()->create(['user_id' => $patientUser->id]);

    $prescription = Prescription::factory()
        ->for($doctor)
        ->for($patient)
        ->create();

    $token = $patientUser->createToken('test')->plainTextToken;

    $response = $this->withToken($token)
        ->get("/api/prescriptions/{$prescription->id}/pdf");

    $response->assertSuccessful();
    $response->assertHeader('Content-Type', 'application/pdf');
});

test('non-owner doctor receives 404 when downloading PDF', function () {
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
        ->get("/api/prescriptions/{$prescription->id}/pdf");

    $response->assertNotFound();
});

test('non-existent prescription returns 404 when downloading PDF', function () {
    $doctorUser = User::factory()->doctor()->create();
    Doctor::factory()->create(['user_id' => $doctorUser->id]);

    $token = $doctorUser->createToken('test')->plainTextToken;

    $response = $this->withToken($token)
        ->get('/api/prescriptions/999999/pdf');

    $response->assertNotFound();
});
