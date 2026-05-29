<?php

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// --------------------------------------------------------------------
// Admin Metrics — spec scenarios
// --------------------------------------------------------------------

test('admin gets full metrics with correct JSON structure', function () {
    $admin = User::factory()->admin()->create();
    $doctorUser = User::factory()->doctor()->create();
    $doctor = Doctor::factory()->create(['user_id' => $doctorUser->id]);
    $patientUser = User::factory()->patient()->create();
    $patient = Patient::factory()->create(['user_id' => $patientUser->id]);

    Prescription::factory()
        ->for($doctor)
        ->for($patient)
        ->count(3)
        ->create(['status' => 'pending']);

    Prescription::factory()
        ->for($doctor)
        ->for($patient)
        ->count(2)
        ->create(['status' => 'consumed']);

    $token = $admin->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/admin/metrics');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'totals' => ['doctors', 'patients', 'prescriptions'],
                'by_status' => ['pending', 'consumed'],
                'by_day',
                'top_doctors',
            ],
        ]);

    $data = $response->json('data');
    expect($data['totals']['doctors'])->toBe(1);
    expect($data['totals']['patients'])->toBe(1);
    expect($data['totals']['prescriptions'])->toBe(5);
    expect($data['by_status']['pending'])->toBe(3);
    expect($data['by_status']['consumed'])->toBe(2);
    expect($data['by_day'])->not->toBeEmpty();
    expect($data['top_doctors'])->not->toBeEmpty();
    expect($data['top_doctors'][0]['doctor_id'])->toBe($doctor->id);
    expect($data['top_doctors'][0]['doctor_name'])->toBe($doctorUser->name);
    expect($data['top_doctors'][0]['count'])->toBe(5);
});

test('date range filters prescription metrics but not global totals', function () {
    $admin = User::factory()->admin()->create();
    $doctorUser = User::factory()->doctor()->create();
    $doctor = Doctor::factory()->create(['user_id' => $doctorUser->id]);
    $patientUser = User::factory()->patient()->create();
    $patient = Patient::factory()->create(['user_id' => $patientUser->id]);

    // Inside range
    Prescription::factory()
        ->for($doctor)
        ->for($patient)
        ->create([
            'status' => 'pending',
            'created_at' => '2026-05-15',
        ]);

    // Outside range (before from)
    Prescription::factory()
        ->for($doctor)
        ->for($patient)
        ->create([
            'status' => 'consumed',
            'created_at' => '2026-04-01',
        ]);

    $token = $admin->createToken('test')->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/admin/metrics?from=2026-05-01&to=2026-05-31');

    $response->assertSuccessful();
    $data = $response->json('data');

    // Global totals unaffected
    expect($data['totals']['doctors'])->toBe(1);
    expect($data['totals']['patients'])->toBe(1);

    // Prescription metrics scoped to range
    expect($data['totals']['prescriptions'])->toBe(1);
    expect($data['by_status']['pending'])->toBe(1);
    expect($data['by_status']['consumed'])->toBe(0);
    expect($data['by_day'])->toHaveCount(1);
    expect($data['top_doctors'][0]['count'])->toBe(1);
});

test('empty data returns safe zeros', function () {
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/admin/metrics');

    $response->assertSuccessful();
    $data = $response->json('data');

    expect($data['totals']['doctors'])->toBe(0);
    expect($data['totals']['patients'])->toBe(0);
    expect($data['totals']['prescriptions'])->toBe(0);
    expect($data['by_status']['pending'])->toBe(0);
    expect($data['by_status']['consumed'])->toBe(0);
    expect($data['by_day'])->toEqual([]);
    expect($data['top_doctors'])->toEqual([]);
});

test('non-admin doctor receives 403', function () {
    $doctorUser = User::factory()->doctor()->create();
    $token = $doctorUser->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/admin/metrics');

    $response->assertForbidden();
});

test('unauthenticated request receives 401', function () {
    $response = $this->getJson('/api/admin/metrics');

    $response->assertUnauthorized();
});

test('invalid date range is rejected with validation error', function () {
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('test')->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/admin/metrics?from=invalid');

    $response->assertUnprocessable()
        ->assertJsonStructure(['message', 'code', 'details']);
});

test('from after to is rejected', function () {
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('test')->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/admin/metrics?from=2026-06-01&to=2026-05-01');

    $response->assertUnprocessable()
        ->assertJsonStructure(['message', 'code', 'details']);
});
