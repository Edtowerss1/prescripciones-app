<?php

// Spec: api-foundation — Frontend origin is allowed (preflight on api/* path)
test('cors preflight allows configured frontend origin', function () {
    $response = $this->optionsJson('/api/auth/login', [], [
        'Origin' => 'http://localhost:5173',
        'Access-Control-Request-Method' => 'POST',
    ]);

    $response->assertStatus(204)
        ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173');
});

// Spec: api-foundation — Unknown origin is rejected
test('cors preflight rejects unknown origin', function () {
    $response = $this->optionsJson('/api/auth/login', [], [
        'Origin' => 'https://evil.com',
        'Access-Control-Request-Method' => 'POST',
    ]);

    expect($response->headers->get('Access-Control-Allow-Origin'))
        ->not->toBe('https://evil.com');
});

// Standard CORS headers on preflight
test('cors preflight includes standard CORS headers', function () {
    $response = $this->optionsJson('/api/auth/login', [], [
        'Origin' => 'http://localhost:5173',
        'Access-Control-Request-Method' => 'POST',
    ]);

    $response->assertStatus(204)
        ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173')
        ->assertHeader('Access-Control-Allow-Methods', 'POST');
});
