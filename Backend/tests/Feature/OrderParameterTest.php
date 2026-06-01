<?php

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// --------------------------------------------------------------------
// Order parameter — GET /api/prescriptions?order=asc|desc
// --------------------------------------------------------------------

test('doctor lists prescriptions in ascending order', function () {
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

    $response = $this->withToken($token)->getJson('/api/prescriptions?order=asc');

    $response->assertSuccessful();

    $data = $response->json('data');
    expect($data)->toHaveLength(3);

    $ids = collect($data)->pluck('id');
    expect($ids->values()->toArray())->toBe($ids->sort()->values()->toArray());
});

test('invalid order value returns 422', function () {
    $doctorUser = User::factory()->doctor()->create();
    $doctor = Doctor::factory()->create(['user_id' => $doctorUser->id]);
    $token = $doctorUser->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/prescriptions?order=invalid');

    $response->assertUnprocessable()
        ->assertJsonStructure(['message', 'code', 'details'])
        ->assertJsonPath('code', 'VALIDATION_ERROR');
});
