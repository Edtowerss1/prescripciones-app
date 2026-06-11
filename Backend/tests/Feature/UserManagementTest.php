<?php

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// --------------------------------------------------------------------
// User List — GET /api/users
// --------------------------------------------------------------------

test('admin can list users paginated', function () {
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('test-token')->plainTextToken;

    User::factory()->doctor()->create();
    User::factory()->patient()->create();

    $response = $this->withToken($token)->getJson('/api/users');

    $response->assertOk()
        ->assertJsonStructure(['data', 'meta', 'links'])
        ->assertJsonCount(3, 'data'); // admin + doctor + patient
});

test('user list filter by role doctor', function () {
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('test-token')->plainTextToken;

    User::factory()->doctor()->create(['name' => 'Dr. Smith']);
    User::factory()->patient()->create(['name' => 'Patient Jane']);

    $response = $this->withToken($token)->getJson('/api/users?role=doctor');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.role', 'doctor');
});

test('user list search by query matches name', function () {
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('test-token')->plainTextToken;

    User::factory()->doctor()->create(['name' => 'Dr. Smith']);
    User::factory()->doctor()->create(['name' => 'Dr. Jones']);

    $response = $this->withToken($token)->getJson('/api/users?query=Smith');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Dr. Smith');
});

test('user list search by query matches email', function () {
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('test-token')->plainTextToken;

    User::factory()->doctor()->create(['email' => 'smith@test.com']);
    User::factory()->doctor()->create(['email' => 'jones@test.com']);

    $response = $this->withToken($token)->getJson('/api/users?query=smith@test');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.email', 'smith@test.com');
});

test('user list rejects doctor role', function () {
    $doctor = User::factory()->doctor()->create();
    $token = $doctor->createToken('test-token')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/users');

    $response->assertForbidden()
        ->assertJsonStructure(['message', 'code', 'details']);
});

test('user list rejects patient role', function () {
    $patient = User::factory()->patient()->create();
    $token = $patient->createToken('test-token')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/users');

    $response->assertForbidden()
        ->assertJsonStructure(['message', 'code', 'details']);
});

test('user list rejects unauthenticated request', function () {
    $response = $this->getJson('/api/users');

    $response->assertUnauthorized()
        ->assertJsonStructure(['message', 'code', 'details']);
});

test('user list invalid role returns 422', function () {
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('test-token')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/users?role=supervisor');

    $response->assertUnprocessable()
        ->assertJsonStructure(['message', 'code', 'details'])
        ->assertJsonPath('code', 'VALIDATION_ERROR');
});

test('user list query too long returns 422', function () {
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('test-token')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/users?query='.str_repeat('a', 256));

    $response->assertUnprocessable()
        ->assertJsonStructure(['message', 'code', 'details'])
        ->assertJsonPath('code', 'VALIDATION_ERROR');
});

// --------------------------------------------------------------------
// Create User — POST /api/users
// --------------------------------------------------------------------

test('admin can create user with doctor role and auto-create doctor profile', function () {
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('test-token')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/users', [
        'name' => 'New Doctor',
        'email' => 'newdoctor@test.com',
        'password' => 'secret123',
        'role' => 'doctor',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['id', 'name', 'email', 'role'])
        ->assertJsonPath('name', 'New Doctor')
        ->assertJsonPath('role', 'doctor');

    $this->assertDatabaseHas('users', [
        'email' => 'newdoctor@test.com',
        'name' => 'New Doctor',
    ]);

    $user = User::where('email', 'newdoctor@test.com')->first();
    expect($user)->not->toBeNull();
    expect($user->hasRole('doctor'))->toBeTrue();
    expect($user->doctor)->not->toBeNull();
    expect($user->doctor->specialty)->toBeNull();
});

test('admin can create user with patient role and auto-create patient profile', function () {
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('test-token')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/users', [
        'name' => 'New Patient',
        'email' => 'newpatient@test.com',
        'password' => 'secret123',
        'role' => 'patient',
    ]);

    $response->assertCreated()
        ->assertJsonPath('role', 'patient');

    $user = User::where('email', 'newpatient@test.com')->first();
    expect($user)->not->toBeNull();
    expect($user->hasRole('patient'))->toBeTrue();
    expect($user->patient)->not->toBeNull();
    expect($user->patient->birth_date)->toBeNull();
});

test('admin can create user with admin role', function () {
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('test-token')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/users', [
        'name' => 'New Admin',
        'email' => 'newadmin@test.com',
        'password' => 'secret123',
        'role' => 'admin',
    ]);

    $response->assertCreated()
        ->assertJsonPath('role', 'admin');

    $user = User::where('email', 'newadmin@test.com')->first();
    expect($user)->not->toBeNull();
    expect($user->hasRole('admin'))->toBeTrue();
    expect($user->doctor)->toBeNull();
    expect($user->patient)->toBeNull();
});

test('create user duplicate email returns 422', function () {
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('test-token')->plainTextToken;

    User::factory()->create(['email' => 'existing@test.com']);

    $response = $this->withToken($token)->postJson('/api/users', [
        'name' => 'Duplicate',
        'email' => 'existing@test.com',
        'password' => 'secret123',
        'role' => 'patient',
    ]);

    $response->assertUnprocessable()
        ->assertJsonStructure(['message', 'code', 'details'])
        ->assertJsonPath('code', 'VALIDATION_ERROR');
});

test('create user invalid role returns 422', function () {
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('test-token')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/users', [
        'name' => 'Invalid',
        'email' => 'invalid@test.com',
        'password' => 'secret123',
        'role' => 'supervisor',
    ]);

    $response->assertUnprocessable()
        ->assertJsonStructure(['message', 'code', 'details'])
        ->assertJsonPath('code', 'VALIDATION_ERROR');
});

test('create user missing fields returns 422', function () {
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('test-token')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/users', []);

    $response->assertUnprocessable()
        ->assertJsonStructure(['message', 'code', 'details'])
        ->assertJsonPath('code', 'VALIDATION_ERROR');
});

test('create user rejects doctor role', function () {
    $doctor = User::factory()->doctor()->create();
    $token = $doctor->createToken('test-token')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/users', [
        'name' => 'Should Not',
        'email' => 'shouldnot@test.com',
        'password' => 'secret123',
        'role' => 'doctor',
    ]);

    $response->assertForbidden()
        ->assertJsonStructure(['message', 'code', 'details']);
});

test('create user rejects unauthenticated request', function () {
    $response = $this->postJson('/api/users', [
        'name' => 'Should Not',
        'email' => 'shouldnot@test.com',
        'password' => 'secret123',
        'role' => 'doctor',
    ]);

    $response->assertUnauthorized()
        ->assertJsonStructure(['message', 'code', 'details']);
});
