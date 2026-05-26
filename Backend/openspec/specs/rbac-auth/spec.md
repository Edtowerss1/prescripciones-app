# rbac-auth Specification

## Purpose

Defines Role-Based Access Control using `spatie/laravel-permission` with roles `admin`, `doctor`, and `patient`, protected via spatie's RoleMiddleware.

## Requirements

### Requirement: Spatie Permission Tables

The application SHALL have the spatie/laravel-permission database tables (`roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`).

#### Scenario: Tables exist after migration

- GIVEN the spatie migration has run
- WHEN the database schema is inspected
- THEN the `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, and `role_has_permissions` tables SHALL exist

### Requirement: User Model Uses HasRoles Trait

The `User` model SHALL use Spatie's `HasRoles` trait for role management.

#### Scenario: HasRoles provides role methods

- GIVEN a `User` instance with role `doctor`
- WHEN `hasRole('doctor')` is called
- THEN it SHALL return `true`
- AND `hasRole('admin')` SHALL return `false`

### Requirement: Role Middleware (Spatie)

The application SHALL use `Spatie\Permission\Middleware\RoleMiddleware` aliased as `role` to restrict route access based on the authenticated user's role.

#### Scenario: User with required role is allowed

- GIVEN a route protected by `role:admin`
- WHEN an authenticated admin user accesses it
- THEN the response status SHALL be 200

#### Scenario: User without required role is forbidden

- GIVEN a route protected by `role:admin`
- WHEN an authenticated patient user accesses it
- THEN the response status SHALL be 403

#### Scenario: Unauthenticated user on role-protected route

- GIVEN a route protected by `role:admin`
- WHEN an unauthenticated request is sent
- THEN the response status SHALL be 401

#### Scenario: Multiple roles allowed via pipe

- GIVEN a route protected by `role:admin|doctor`
- WHEN an authenticated doctor accesses it
- THEN the response status SHALL be 200

#### Scenario: Middleware alias is registered

- GIVEN `bootstrap/app.php` is configured
- WHEN the middleware aliases are inspected
- THEN `role` SHALL be aliased to `Spatie\Permission\Middleware\RoleMiddleware`

### Requirement: AuthController

The application SHALL provide an `AuthController` with `login`, `profile`, and `logout` methods at `/api/auth/*`.

#### Scenario: Login with valid credentials

- GIVEN a user exists
- WHEN a POST request is sent to `/api/auth/login` with valid email and password
- THEN the response status SHALL be 201
- AND the response SHALL contain a `token` field

#### Scenario: Login with invalid credentials

- GIVEN a user exists
- WHEN a POST request is sent to `/api/auth/login` with wrong password
- THEN the response status SHALL be 401

#### Scenario: Profile returns authenticated user with role

- GIVEN an authenticated user
- WHEN a GET request is sent to `/api/auth/profile`
- THEN the response status SHALL be 200
- AND the response SHALL include `id`, `name`, `email`, and `role`

#### Scenario: Logout revokes current token

- GIVEN an authenticated user with a valid token
- WHEN a DELETE request is sent to `/api/auth/logout`
- THEN the response status SHALL be 204
- AND subsequent requests with the same token SHALL return 401

### Requirement: UserFactory and DatabaseSeeder Support Roles

The `UserFactory` SHALL assign a default role of `patient` via `assignRole`, and `DatabaseSeeder` SHALL create users with distinct spatie roles.

#### Scenario: Factory defaults to patient

- GIVEN a `User` is created via factory without specifying a role
- WHEN the model is inspected
- THEN `hasRole('patient')` SHALL return `true`

#### Scenario: Seeder creates admin user

- GIVEN the database seeder runs
- WHEN the database is seeded
- THEN at least one user with role `admin` SHALL exist

### Requirement: Existing Token Tests Remain Passing

All existing API tests in `tests/Feature/ApiTest.php` MUST continue to pass after the RBAC and controller extraction changes.

#### Scenario: All existing token tests pass

- GIVEN all RBAC and auth controller changes have been applied
- WHEN `php artisan test --compact --filter=ApiTest` is executed
- THEN all tests SHALL pass
