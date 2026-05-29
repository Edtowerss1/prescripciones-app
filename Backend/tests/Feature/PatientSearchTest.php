<?php

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

// --------------------------------------------------------------------
// Patient Search — GET /api/patients
// --------------------------------------------------------------------

function createPatientWithUser(array $userAttributes = []): Patient
{
    Role::findOrCreate('patient', 'api');

    $patient = Patient::factory()->create();
    $patient->user->update($userAttributes);
    $patient->user->assignRole('patient');

    return $patient;
}

test('search by name returns matching patients', function () {
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('test-token')->plainTextToken;

    createPatientWithUser(['name' => 'John Doe']);
    createPatientWithUser(['name' => 'Jane Smith']);

    $response = $this->withToken($token)->getJson('/api/patients?query=John');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.user.name', 'John Doe');
});

test('no query returns all patients paginated', function () {
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('test-token')->plainTextToken;

    createPatientWithUser(['name' => 'Patient A']);
    createPatientWithUser(['name' => 'Patient B']);

    $response = $this->withToken($token)->getJson('/api/patients');

    $response->assertOk()
        ->assertJsonStructure(['data', 'meta', 'links'])
        ->assertJsonCount(2, 'data');
});

test('patient search is ordered by created_at desc', function () {
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('test-token')->plainTextToken;

    createPatientWithUser(['name' => 'Oldest']);
    $this->travel(1)->hour();
    createPatientWithUser(['name' => 'Newest']);

    $response = $this->withToken($token)->getJson('/api/patients');

    $response->assertOk();
    $names = collect($response->json('data'))->pluck('user.name');
    expect($names->first())->toBe('Newest');
});

test('patient search rejects unauthorized role', function () {
    $patientUser = User::factory()->patient()->create();
    $token = $patientUser->createToken('test-token')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/patients');

    $response->assertForbidden();
});

test('patient search rejects unauthenticated request', function () {
    $response = $this->getJson('/api/patients');

    $response->assertUnauthorized();
});

test('patient resource does not expose user_id', function () {
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('test-token')->plainTextToken;

    createPatientWithUser(['name' => 'John Doe']);

    $response = $this->withToken($token)->getJson('/api/patients?query=John');

    $response->assertOk()
        ->assertJsonMissing(['user_id']);
});

test('empty search query returns all patients', function () {
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('test-token')->plainTextToken;

    createPatientWithUser(['name' => 'Patient A']);
    createPatientWithUser(['name' => 'Patient B']);

    $response = $this->withToken($token)->getJson('/api/patients?query=');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

test('patient search respects pagination limit', function () {
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('test-token')->plainTextToken;

    foreach (range(1, 5) as $i) {
        createPatientWithUser(['name' => "Patient {$i}"]);
    }

    $response = $this->withToken($token)->getJson('/api/patients?limit=2');

    $response->assertOk();
    expect(count($response->json('data')))->toBeLessThanOrEqual(2);
});
