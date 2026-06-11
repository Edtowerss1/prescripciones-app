<?php

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

// --------------------------------------------------------------------
// Doctor List — GET /api/doctors
// --------------------------------------------------------------------

function createDoctorWithUser(array $userAttributes = []): Doctor
{
    Role::findOrCreate('doctor', 'api');

    $doctor = Doctor::factory()->create();
    $doctor->user->update($userAttributes);
    $doctor->user->assignRole('doctor');

    return $doctor;
}

test('admin can list doctors paginated', function () {
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('test-token')->plainTextToken;

    createDoctorWithUser(['name' => 'Dr. Smith']);
    createDoctorWithUser(['name' => 'Dr. Jones']);

    $response = $this->withToken($token)->getJson('/api/doctors');

    $response->assertOk()
        ->assertJsonStructure(['data', 'meta', 'links'])
        ->assertJsonCount(2, 'data');
});

test('doctor list search by name', function () {
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('test-token')->plainTextToken;

    createDoctorWithUser(['name' => 'Dr. Smith']);
    createDoctorWithUser(['name' => 'Dr. Jones']);

    $response = $this->withToken($token)->getJson('/api/doctors?query=Smith');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.user.name', 'Dr. Smith');
});

test('doctor list respects pagination limit', function () {
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('test-token')->plainTextToken;

    foreach (range(1, 5) as $i) {
        createDoctorWithUser(['name' => "Dr. {$i}"]);
    }

    $response = $this->withToken($token)->getJson('/api/doctors?limit=2');

    $response->assertOk();
    expect(count($response->json('data')))->toBeLessThanOrEqual(2);
});

test('doctor list rejects doctor role', function () {
    $doctor = User::factory()->doctor()->create();
    $token = $doctor->createToken('test-token')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/doctors');

    $response->assertForbidden()
        ->assertJsonStructure(['message', 'code', 'details']);
});

test('doctor list rejects patient role', function () {
    $patient = User::factory()->patient()->create();
    $token = $patient->createToken('test-token')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/doctors');

    $response->assertForbidden()
        ->assertJsonStructure(['message', 'code', 'details']);
});

test('doctor list rejects unauthenticated request', function () {
    $response = $this->getJson('/api/doctors');

    $response->assertUnauthorized()
        ->assertJsonStructure(['message', 'code', 'details']);
});

test('doctor list returns empty result set for non-matching query', function () {
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('test-token')->plainTextToken;

    createDoctorWithUser(['name' => 'Dr. Smith']);

    $response = $this->withToken($token)->getJson('/api/doctors?query=NonExistent');

    $response->assertOk()
        ->assertJsonCount(0, 'data');
});

test('doctor list invalid page returns 422', function () {
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('test-token')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/doctors?page=-1');

    $response->assertUnprocessable()
        ->assertJsonStructure(['message', 'code', 'details'])
        ->assertJsonPath('code', 'VALIDATION_ERROR');
});

test('doctor list limit over max returns 422', function () {
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('test-token')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/doctors?limit=101');

    $response->assertUnprocessable()
        ->assertJsonStructure(['message', 'code', 'details'])
        ->assertJsonPath('code', 'VALIDATION_ERROR');
});
