<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Task 3.1
it('returns JSON from the API root', function () {
    $response = $this->getJson('/api');

    $response->assertOk()
        ->assertJson(['status' => 'ok']);
});

// Task 3.2
it('issues a token with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'jane@example.com',
        'password' => bcrypt('secret123'),
    ]);

    $response = $this->postJson('/api/tokens', [
        'email' => 'jane@example.com',
        'password' => 'secret123',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['token']);
});

// Task 3.3
it('refuses token with invalid credentials', function () {
    User::factory()->create([
        'email' => 'jane@example.com',
        'password' => bcrypt('secret123'),
    ]);

    $response = $this->postJson('/api/tokens', [
        'email' => 'jane@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertUnauthorized();
});

// Task 3.4
it('rejects unauthenticated request to protected route', function () {
    $response = $this->getJson('/api/user');

    $response->assertUnauthorized();
});

// Task 3.5
it('returns the authenticated user with a valid token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/user');

    $response->assertOk()
        ->assertJson(['id' => $user->id]);
});

// Task 3.6
it('revokes a token and rejects subsequent requests', function () {
    $user = User::factory()->create();
    $tokenObj = $user->createToken('revocable');
    $token = $tokenObj->plainTextToken;
    $tokenId = $tokenObj->accessToken->id;

    $this->withToken($token)
        ->deleteJson("/api/tokens/{$tokenId}")
        ->assertNoContent();

    // Forget cached auth guards so the revoked token is re-checked
    app('auth')->forgetGuards();

    $this->withToken($token)
        ->getJson('/api/user')
        ->assertUnauthorized();
});

// Task 3.7
it('returns 404 for former web root', function () {
    $response = $this->get('/');

    $response->assertNotFound();
});

// Task 3.8
it('returns JSON 422 on validation errors', function () {
    $response = $this->postJson('/api/tokens', [
        'email' => 'not-an-email',
        'password' => '',
    ]);

    $response->assertUnprocessable()
        ->assertJsonStructure(['message', 'errors']);
});

// Task 3.9
it('returns JSON 404 on non-existent API route', function () {
    $response = $this->getJson('/api/non-existent');

    $response->assertNotFound()
        ->assertJsonStructure(['message']);
});

// Task 3.10
it('health endpoint still works', function () {
    $response = $this->get('/up');

    $response->assertOk();
});

// Task 3.11
it('application boots without node_modules', function () {
    $response = $this->getJson('/api');

    $response->assertOk();
});

// Task 5.4 — spec: Invalid token is rejected (previously only covered missing token, not bad token)
it('rejects request with random invalid token', function () {
    $response = $this->withToken('invalid-token-12345')->getJson('/api/user');

    $response->assertUnauthorized();
});

// API-only backend: default guard should be api, not web
it('defaults to the api guard', function () {
    expect(config('auth.defaults.guard'))->toBe('api');
});
