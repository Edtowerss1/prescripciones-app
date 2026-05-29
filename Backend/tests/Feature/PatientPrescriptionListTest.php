<?php

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// --------------------------------------------------------------------
// Patient list: own prescriptions, filtered by status, paginated
// --------------------------------------------------------------------

test('patient_lists_own_prescriptions', function () {
    $doctorUser = User::factory()->doctor()->create();
    $doctor = Doctor::factory()->create(['user_id' => $doctorUser->id]);
    $patientUser = User::factory()->patient()->create();
    $patient = Patient::factory()->create(['user_id' => $patientUser->id]);

    Prescription::factory()
        ->for($doctor)
        ->for($patient)
        ->count(3)
        ->sequence(
            ['created_at' => now()->subDays(5)],
            ['created_at' => now()->subDays(3)],
            ['created_at' => now()->subDay()],
        )
        ->create();

    $token = $patientUser->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/me/prescriptions');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [['id', 'code', 'status', 'doctor', 'patient', 'created_at']],
            'links',
            'meta',
        ]);

    $ids = collect($response->json('data'))->pluck('id');
    expect($ids->values()->toArray())->toBe($ids->sortDesc()->values()->toArray());
});

test('patient_filters_by_status_consumed', function () {
    $doctorUser = User::factory()->doctor()->create();
    $doctor = Doctor::factory()->create(['user_id' => $doctorUser->id]);
    $patientUser = User::factory()->patient()->create();
    $patient = Patient::factory()->create(['user_id' => $patientUser->id]);

    Prescription::factory()
        ->for($doctor)
        ->for($patient)
        ->count(2)
        ->create(['status' => 'pending']);

    Prescription::factory()
        ->for($doctor)
        ->for($patient)
        ->create(['status' => 'consumed', 'consumed_at' => now()]);

    $token = $patientUser->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/me/prescriptions?status=consumed');

    $response->assertSuccessful();
    expect($response->json('data'))->toHaveLength(1);
    expect($response->json('data.0.status'))->toBe('consumed');
});

test('pagination_works_for_patient_list', function () {
    $doctorUser = User::factory()->doctor()->create();
    $doctor = Doctor::factory()->create(['user_id' => $doctorUser->id]);
    $patientUser = User::factory()->patient()->create();
    $patient = Patient::factory()->create(['user_id' => $patientUser->id]);

    Prescription::factory()
        ->for($doctor)
        ->for($patient)
        ->count(5)
        ->create();

    $token = $patientUser->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/me/prescriptions?limit=2');

    $response->assertSuccessful();
    expect($response->json('meta.per_page'))->toBe(2);
    expect($response->json('data'))->toHaveLength(2);
});
