<?php

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// --------------------------------------------------------------------
// Doctor list: paginated, ordered DESC, filtered by status and date
// --------------------------------------------------------------------

test('doctor_lists_own_prescriptions_paginated_ordered_desc', function () {
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

    $token = $doctorUser->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/prescriptions');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [['id', 'code', 'status', 'doctor', 'patient', 'created_at']],
            'links',
            'meta',
        ]);

    $ids = collect($response->json('data'))->pluck('id');
    expect($ids->values()->toArray())->toBe($ids->sortDesc()->values()->toArray());
});

test('doctor_filters_by_status_pending', function () {
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

    $token = $doctorUser->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/prescriptions?status=pending');

    $response->assertSuccessful();
    expect($response->json('data'))->toHaveLength(2);
    foreach ($response->json('data') as $item) {
        expect($item['status'])->toBe('pending');
    }
});

test('doctor_filters_by_date_range', function () {
    $doctorUser = User::factory()->doctor()->create();
    $doctor = Doctor::factory()->create(['user_id' => $doctorUser->id]);
    $patientUser = User::factory()->patient()->create();
    $patient = Patient::factory()->create(['user_id' => $patientUser->id]);

    Prescription::factory()
        ->for($doctor)
        ->for($patient)
        ->create(['created_at' => '2026-03-01']);

    Prescription::factory()
        ->for($doctor)
        ->for($patient)
        ->create(['created_at' => '2026-05-15']);

    Prescription::factory()
        ->for($doctor)
        ->for($patient)
        ->create(['created_at' => '2026-07-01']);

    $token = $doctorUser->createToken('test')->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/prescriptions?from=2026-01-01&to=2026-06-01');

    $response->assertSuccessful();
    expect($response->json('data'))->toHaveLength(2);
});

test('pagination_respects_limit_capped_at_100', function () {
    $doctorUser = User::factory()->doctor()->create();
    $doctor = Doctor::factory()->create(['user_id' => $doctorUser->id]);
    $patientUser = User::factory()->patient()->create();
    $patient = Patient::factory()->create(['user_id' => $patientUser->id]);

    // Create 5 prescriptions so we have data
    Prescription::factory()
        ->for($doctor)
        ->for($patient)
        ->count(5)
        ->create();

    $token = $doctorUser->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/prescriptions?limit=101');

    $response->assertSuccessful();
    expect($response->json('meta.per_page'))->toBe(100);
});
