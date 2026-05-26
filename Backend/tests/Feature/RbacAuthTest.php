<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// --------------------------------------------------------------------
// Spatie Roles — User
// --------------------------------------------------------------------

test('spatie roles are assigned via factory state', function () {
    $user = User::factory()->patient()->create();

    expect($user->hasRole('patient'))->toBeTrue();
    expect($user->getRoleNames())->toHaveCount(1);
});

test('factory defaults without a role assignment', function () {
    $user = User::factory()->create();

    expect($user->getRoleNames())->toHaveCount(0);
});

test('factory admin state creates user with admin role', function () {
    $user = User::factory()->admin()->create();

    expect($user->hasRole('admin'))->toBeTrue();
    expect($user->getRoleNames())->toHaveCount(1);
});

test('factory doctor state creates user with doctor role', function () {
    $user = User::factory()->doctor()->create();

    expect($user->hasRole('doctor'))->toBeTrue();
    expect($user->getRoleNames())->toHaveCount(1);
});

// --------------------------------------------------------------------
// DatabaseSeeder
// --------------------------------------------------------------------

test('seeder creates a user with admin role', function () {
    $this->seed();

    $admin = User::where('email', 'admin@test.com')->first();

    expect($admin)->not->toBeNull()
        ->and($admin->hasRole('admin'))->toBeTrue()
        ->and($admin->hasRole('patient'))->toBeFalse()
        ->and($admin->getRoleNames())->toHaveCount(1);
});

test('seeder creates a user with patient role', function () {
    $this->seed();

    $patient = User::where('email', 'patient@test.com')->first();

    expect($patient)->not->toBeNull()
        ->and($patient->hasRole('patient'))->toBeTrue()
        ->and($patient->getRoleNames())->toHaveCount(1);
});

// --------------------------------------------------------------------
// AuthController — Login
// --------------------------------------------------------------------

test('POST /api/auth/login issues token with valid credentials', function () {
    User::factory()->create([
        'email' => 'doctor@example.com',
        'password' => bcrypt('secret123'),
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'doctor@example.com',
        'password' => 'secret123',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['access_token', 'token_type', 'user']);
});

test('POST /api/auth/login rejects invalid credentials', function () {
    User::factory()->create([
        'email' => 'doctor@example.com',
        'password' => bcrypt('secret123'),
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => 'doctor@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertUnauthorized();
});

test('POST /api/auth/login returns validation errors for missing fields', function () {
    $response = $this->postJson('/api/auth/login', []);

    $response->assertUnprocessable()
        ->assertJsonStructure(['message', 'errors']);
});

// --------------------------------------------------------------------
// AuthController — Profile
// --------------------------------------------------------------------

test('GET /api/auth/profile returns authenticated user with role', function () {
    $user = User::factory()->doctor()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/auth/profile');

    $response->assertSuccessful()
        ->assertJson([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => 'doctor',
        ]);
});

test('GET /api/auth/profile rejects unauthenticated requests', function () {
    $response = $this->getJson('/api/auth/profile');

    $response->assertUnauthorized();
});

// --------------------------------------------------------------------
// AuthController — Logout
// --------------------------------------------------------------------

test('POST /api/auth/logout revokes current token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('revocable')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/auth/logout')
        ->assertNoContent();

    app('auth')->forgetGuards();

    $this->withToken($token)
        ->getJson('/api/auth/profile')
        ->assertUnauthorized();
});

// --------------------------------------------------------------------
// Spatie RoleMiddleware
// --------------------------------------------------------------------

test('role middleware allows user with required role', function () {
    $admin = User::factory()->admin()->create();
    $token = $admin->createToken('test-token')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/admin-only');

    $response->assertSuccessful();
});

test('role middleware forbids user without required role', function () {
    $patient = User::factory()->patient()->create();
    $token = $patient->createToken('test-token')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/admin-only');

    $response->assertForbidden();
});

test('role middleware rejects unauthenticated user', function () {
    $response = $this->getJson('/api/admin-only');

    $response->assertUnauthorized();
});

test('role middleware supports multiple roles via pipe', function () {
    $doctor = User::factory()->doctor()->create();
    $token = $doctor->createToken('test-token')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/admin-or-doctor');

    $response->assertSuccessful();
});
