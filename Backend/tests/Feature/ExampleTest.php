<?php

// Verifies the response Content-Type for the web root 404.
// ApiTest covers the status code; this test adds Content-Type coverage.
// The web root is not under /api/* so shouldRenderJsonWhen does not apply.
test('the application root returns HTML 404 in API-only mode', function () {
    $response = $this->get('/');

    $response->assertNotFound();
    expect($response->headers->get('Content-Type'))->toContain('text/html');
});
