<?php

// Verifies the response Content-Type for the web root 404.
// With shouldRenderJsonWhen set to always-true, all errors return JSON.
test('the application root returns JSON 404 in API-only mode', function () {
    $response = $this->get('/');

    $response->assertNotFound();
    expect($response->headers->get('Content-Type'))->toContain('application/json');
});
