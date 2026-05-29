# Delta for api-authentication

## ADDED Requirements

### Requirement: Profile Endpoint

The system MUST provide `GET /api/auth/profile` returning the authenticated user via `UserResource`. Protected by `auth:sanctum`.

#### Scenario: Authenticated user gets profile

- GIVEN a user with a valid Sanctum token
- WHEN `GET /api/auth/profile`
- THEN 200 with `UserResource` containing `{id, name, email, role}`

#### Scenario: Unauthenticated request is rejected

- GIVEN no valid token
- WHEN `GET /api/auth/profile`
- THEN 401 with `{message, code: "UNAUTHORIZED"}`

### Requirement: UserResource Contract

`UserResource` MUST return `{id, name, email, role}` where `role` is the user's first Spatie role name via `getRoleNames()->first()`.

#### Scenario: Resource shape

- GIVEN a user with role "doctor"
- WHEN `UserResource` serializes
- THEN response SHALL contain `id`, `name`, `email`, `role: "doctor"`

## MODIFIED Requirements

### Requirement: Token Issuance

The application SHALL provide a login endpoint using `LoginRequest` (email required|email, password required|string) for validation. On success, the response SHALL include a `token` field and a `user` field using `UserResource`. On failure, errors SHALL use the standardized `{message, code, details}` envelope.
(Previously: inline validation and response only contained a `token` field without user data.)

#### Scenario: User logs in with valid credentials

- GIVEN a user exists in the database
- WHEN POST `/api/auth/login` with valid email and password
- THEN 201 with `{token, user: {id, name, email, role}}`

#### Scenario: Login fails with invalid credentials

- GIVEN a user exists in the database
- WHEN POST `/api/auth/login` with incorrect password
- THEN 401 with `{message, code: "UNAUTHORIZED"}`

#### Scenario: Login fails with missing fields

- GIVEN a login request
- WHEN POST `/api/auth/login` with missing email or password
- THEN 422 with `{message, code: "VALIDATION_ERROR", details: {errors: {...}}}`
