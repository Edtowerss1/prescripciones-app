# patient-search Specification

## Purpose

Searchable patient listing for doctors and administrators to find patients when creating prescriptions.

## Requirements

### Requirement: Patient Search Endpoint

The system MUST provide `GET /api/patients` with a `query` parameter to filter patients by `users.name` using a LIKE match. Results SHALL be paginated (default 15, max 100) and ordered by `created_at DESC`. Response MUST use `PatientResource`. Protected by `auth:sanctum` + `role:admin|doctor`.

#### Scenario: Search by name returns matches

- GIVEN authenticated admin and patients linked to users "John" and "Jane"
- WHEN `GET /api/patients?query=John`
- THEN 200 with `PatientResource` collection containing only "John", paginated

#### Scenario: No query returns all patients

- GIVEN authenticated doctor
- WHEN `GET /api/patients`
- THEN 200 with all patients paginated, ordered by created_at DESC

#### Scenario: Unauthorized role blocked

- GIVEN authenticated patient
- WHEN `GET /api/patients`
- THEN 403 Forbidden with `{message, code: "FORBIDDEN"}`

#### Scenario: Unauthenticated blocked

- GIVEN no valid token
- WHEN `GET /api/patients`
- THEN 401 Unauthorized

### Requirement: PatientResource Contract

`PatientResource` MUST return: `id`, `birth_date`, `created_at`, and a `user` object `{id, name, email}` loaded via `whenLoaded()`. Internal keys like `user_id` SHALL NOT be exposed.

#### Scenario: Resource shape is correct

- GIVEN a patient with user relation loaded
- WHEN `PatientResource` serializes
- THEN response contains `id`, `birth_date`, `user.{id,name,email}`
- AND does NOT expose `user_id`
