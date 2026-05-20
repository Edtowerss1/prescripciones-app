# api-authentication Specification

## Purpose

Defines token-based API authentication using Laravel Sanctum for stateless consumers.

## Requirements

### Requirement: Sanctum Token Guard

The application MUST register an `api` authentication guard that uses Sanctum's token driver for bearer token authentication.

#### Scenario: Guard is registered

- GIVEN the auth configuration is loaded
- WHEN `auth('api')` is resolved
- THEN the guard SHALL use the `sanctum` driver
- AND the guard SHALL be the default for API routes

### Requirement: Token Issuance

The application SHALL provide an endpoint to create personal access tokens for authenticated users.

#### Scenario: User creates a token with valid credentials

- GIVEN a user exists in the database
- WHEN a POST request is sent to `/api/tokens` with valid email and password
- THEN the response SHALL be 201
- AND the response body SHALL contain a `token` field with a plain-text bearer token

#### Scenario: Token creation fails with invalid credentials

- GIVEN a user exists in the database
- WHEN a POST request is sent to `/api/tokens` with an incorrect password
- THEN the response status SHALL be 401
- AND the response SHALL be JSON

### Requirement: Protected Route Enforcement

API routes that require authentication MUST reject requests that lack a valid bearer token.

#### Scenario: Authenticated request succeeds

- GIVEN a user has a valid Sanctum token
- WHEN a GET request is sent to a protected route with `Authorization: Bearer {token}`
- THEN the response status SHALL be 200

#### Scenario: Unauthenticated request is rejected

- GIVEN a protected API route exists
- WHEN a GET request is sent without an `Authorization` header
- THEN the response status SHALL be 401
- AND the response SHALL be JSON

#### Scenario: Invalid token is rejected

- GIVEN a protected API route exists
- WHEN a GET request is sent with `Authorization: Bearer invalid-token`
- THEN the response status SHALL be 401

### Requirement: Token Revocation

The application SHALL support revoking API tokens so they can no longer be used for authentication.

#### Scenario: User revokes their own token

- GIVEN a user has an active Sanctum token
- WHEN a DELETE request is sent to `/api/tokens/{id}` with a valid bearer token
- THEN the response status SHALL be 204
- AND subsequent requests with the revoked token SHALL return 401

### Requirement: Sanctum Package Installed

Composer MUST include `laravel/sanctum` as a project dependency.

#### Scenario: Sanctum is installable

- GIVEN `laravel/sanctum` is listed in `composer.json`
- WHEN `composer install` is executed
- THEN the Sanctum service provider and configuration SHALL be available
