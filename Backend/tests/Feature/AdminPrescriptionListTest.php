<?php

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// --------------------------------------------------------------------
// Admin Prescriptions List — GET /api/admin/prescriptions
// --------------------------------------------------------------------

test('admin lists all prescriptions paginated', function () {
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('test')->plainTextToken;

    $doctorUser1 = User::factory()->doctor()->create();
    $doctor1 = Doctor::factory()->create(['user_id' => $doctorUser1->id]);
    $doctorUser2 = User::factory()->doctor()->create();
    $doctor2 = Doctor::factory()->create(['user_id' => $doctorUser2->id]);
    $patientUser = User::factory()->patient()->create();
    $patient = Patient::factory()->create(['user_id' => $patientUser->id]);

    Prescription::factory()
        ->for($doctor1)
        ->for($patient)
        ->count(2)
        ->create();

    Prescription::factory()
        ->for($doctor2)
        ->for($patient)
        ->create();

    $response = $this->withToken($token)->getJson('/api/admin/prescriptions');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [['id', 'code', 'status', 'doctor', 'patient', 'created_at']],
            'links',
            'meta',
        ]);

    expect($response->json('data'))->toHaveLength(3);
});

test('admin filters prescriptions by status and doctor', function () {
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('test')->plainTextToken;

    $doctorUser = User::factory()->doctor()->create();
    $doctor = Doctor::factory()->create(['user_id' => $doctorUser->id]);
    $otherDoctorUser = User::factory()->doctor()->create();
    $otherDoctor = Doctor::factory()->create(['user_id' => $otherDoctorUser->id]);
    $patientUser = User::factory()->patient()->create();
    $patient = Patient::factory()->create(['user_id' => $patientUser->id]);

    // Doctor #1: 2 pending + 1 consumed
    Prescription::factory()
        ->for($doctor)
        ->for($patient)
        ->count(2)
        ->create(['status' => 'pending']);

    Prescription::factory()
        ->for($doctor)
        ->for($patient)
        ->consumed()
        ->create();

    // Doctor #2: 1 pending
    Prescription::factory()
        ->for($otherDoctor)
        ->for($patient)
        ->create(['status' => 'pending']);

    $response = $this->withToken($token)
        ->getJson("/api/admin/prescriptions?status=pending&doctor_id={$doctor->id}");

    $response->assertSuccessful();
    expect($response->json('data'))->toHaveLength(2);
    foreach ($response->json('data') as $item) {
        expect($item['status'])->toBe('pending');
    }
});

test('admin filters prescriptions by patient and date range', function () {
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('test')->plainTextToken;

    $doctorUser = User::factory()->doctor()->create();
    $doctor = Doctor::factory()->create(['user_id' => $doctorUser->id]);
    $patientUser1 = User::factory()->patient()->create();
    $patient1 = Patient::factory()->create(['user_id' => $patientUser1->id]);
    $patientUser2 = User::factory()->patient()->create();
    $patient2 = Patient::factory()->create(['user_id' => $patientUser2->id]);

    // Patient #1: 2 prescriptions within range
    Prescription::factory()
        ->for($doctor)
        ->for($patient1)
        ->create(['created_at' => '2026-03-01']);

    Prescription::factory()
        ->for($doctor)
        ->for($patient1)
        ->create(['created_at' => '2026-05-15']);

    // Patient #2: 1 within range
    Prescription::factory()
        ->for($doctor)
        ->for($patient2)
        ->create(['created_at' => '2026-04-01']);

    // Patient #1: 1 outside range
    Prescription::factory()
        ->for($doctor)
        ->for($patient1)
        ->create(['created_at' => '2026-07-01']);

    $response = $this->withToken($token)
        ->getJson("/api/admin/prescriptions?patient_id={$patient1->id}&from=2026-01-01&to=2026-06-01");

    $response->assertSuccessful();
    expect($response->json('data'))->toHaveLength(2);
});

test('non admin gets 403 on admin prescriptions', function () {
    $doctor = User::factory()->doctor()->create();
    $token = $doctor->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/admin/prescriptions');

    $response->assertForbidden()
        ->assertJsonStructure(['message', 'code', 'details']);
});

test('unauthenticated gets 401 on admin prescriptions', function () {
    $response = $this->getJson('/api/admin/prescriptions');

    $response->assertUnauthorized()
        ->assertJsonStructure(['message', 'code', 'details']);
});
