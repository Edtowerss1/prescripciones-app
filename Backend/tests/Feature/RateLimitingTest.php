<?php

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Cache\RateLimiting\Unlimited;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

// Spec: api-foundation — API rate limiter named 'api' is registered
test('api rate limiter is registered', function () {
    $limiter = RateLimiter::limiter('api');

    expect($limiter)->toBeCallable();
});

// Spec: api-foundation — Rate limiting disabled in test environment
test('api rate limiter returns unlimited in testing environment', function () {
    $limiter = RateLimiter::limiter('api');
    $resolved = call_user_func($limiter, Request::create('/api'));

    expect($resolved)->toBeInstanceOf(Unlimited::class);
});

// Spec: api-foundation — Throttle middleware returns 429 when exceeded
// Uses a dedicated test-only limiter with a low threshold for speed
test('throttle middleware returns 429 when rate limit exceeded', function () {
    // Register a temporary limiter at the start
    RateLimiter::for('test-strict', fn () => Limit::perMinute(2));

    // Create a temporary route that uses the test limiter
    Route::get('/api/_throttle-test', fn () => response()->json(['ok' => true]))
        ->middleware('throttle:test-strict');

    // Exhaust the 2/min limit
    $this->getJson('/api/_throttle-test')->assertOk();
    $this->getJson('/api/_throttle-test')->assertOk();

    // Third request should be throttled
    $this->getJson('/api/_throttle-test')->assertStatus(429);
});
