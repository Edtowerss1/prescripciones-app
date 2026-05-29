# Delta for prescription-lifecycle

## ADDED Requirements

### Requirement: PrescriptionResource Contract

`PrescriptionResource` MUST return `{id, code, status, notes, consumed_at, created_at}` plus relationships via `whenLoaded()`: `doctor: {id, name}`, `patient: {id, name}`, `items[]` (via `PrescriptionItemResource` when relation loaded). Internal keys like `doctor_id`, `patient_id` SHALL NOT be exposed.

#### Scenario: Full detail with items loaded

- GIVEN a prescription with items, doctor, and patient relations loaded
- WHEN `PrescriptionResource` serializes
- THEN response SHALL contain `doctor.{id,name}`, `patient.{id,name}`, `items[]`
- AND SHALL NOT expose `doctor_id` or `patient_id`

#### Scenario: List view without items

- GIVEN a prescription without items relation loaded
- WHEN `PrescriptionResource` serializes
- THEN `items` key SHALL be absent

### Requirement: PrescriptionItemResource Contract

`PrescriptionItemResource` MUST return `{id, name, dosage, quantity, instructions}`. Internal keys like `prescription_id` SHALL NOT be exposed.

#### Scenario: Resource shape

- GIVEN a prescription item
- WHEN `PrescriptionItemResource` serializes
- THEN response contains `id`, `name`, `dosage`, `quantity`, `instructions`
- AND does NOT contain `prescription_id`

## MODIFIED Requirements

### Requirement: Doctor Creates Prescription

The system MUST allow doctors to create prescriptions via `POST /api/prescriptions`, validated by `StorePrescriptionRequest` with nested `items.*` rules (name required|string|max:255, quantity required|integer|min:1, dosage nullable|string, instructions nullable|string). Response SHALL return `PrescriptionResource` with items loaded. Protected by `auth:sanctum` and `role:doctor`.
(Previously: inline validation and raw model arrays.)

#### Scenario: Valid creation

- GIVEN an authenticated doctor and existing patient
- WHEN POST `/api/prescriptions` with patient_id, notes, and ≥1 items
- THEN 201 with `PrescriptionResource` including `items[]` and `status: "pending"`

#### Scenario: Invalid patient_id

- GIVEN an authenticated doctor
- WHEN POST with non-existent `patient_id`
- THEN 422 with `{message, code: "VALIDATION_ERROR", details: {errors: {patient_id: [...]}}}`

#### Scenario: Non-doctor blocked

- GIVEN an authenticated patient
- WHEN POST `/api/prescriptions`
- THEN 403 Forbidden with `code: "FORBIDDEN"`

### Requirement: Doctor Lists Own Prescriptions

The system MUST allow doctors to list their prescriptions via `GET /api/prescriptions`, validated by `PrescriptionFilterRequest` (status nullable|string|in:pending,consumed, from/to nullable|date). Results SHALL be paginated (default 15, max 100), ordered by `created_at DESC`. Response SHALL use `PrescriptionResource` collection without items loaded. Protected by `auth:sanctum` and `role:doctor`.
(Previously: inline validation and raw model arrays.)

#### Scenario: Filter by status

- GIVEN an authenticated doctor with pending and consumed prescriptions
- WHEN GET `/api/prescriptions?status=pending`
- THEN 200 with `PrescriptionResource` collection (no items), only pending, ordered DESC

#### Scenario: Date range filter

- GIVEN an authenticated doctor with prescriptions across multiple dates
- WHEN GET `/api/prescriptions?from=2026-01-01&to=2026-06-01`
- THEN 200 with prescriptions within the date range

#### Scenario: Non-doctor blocked

- GIVEN an authenticated patient
- WHEN GET `/api/prescriptions`
- THEN 403 Forbidden

### Requirement: Prescription Detail

The system MUST provide detail via `GET /api/prescriptions/{id}` using implicit route model binding. Authorization SHALL use `PrescriptionPolicy::view`. Response MUST use `PrescriptionResource` with items loaded. Unauthorized users SHALL receive 404 with `code: "NOT_FOUND"`.
(Previously: raw model arrays; error format unspecified.)

#### Scenario: Owner doctor views prescription

- GIVEN an authenticated doctor who created prescription #1
- WHEN GET `/api/prescriptions/1`
- THEN 200 with `PrescriptionResource` including `items[]`

#### Scenario: Owner patient views prescription

- GIVEN an authenticated patient assigned to prescription #1
- WHEN GET `/api/prescriptions/1`
- THEN 200 with `PrescriptionResource` including `items[]`

#### Scenario: Admin views any prescription

- GIVEN an authenticated admin
- WHEN GET `/api/prescriptions/{any_id}`
- THEN 200 with `PrescriptionResource` including `items[]`

#### Scenario: Non-owner gets 404

- GIVEN an authenticated doctor who did NOT create prescription #5
- WHEN GET `/api/prescriptions/5`
- THEN 404 with `{message, code: "NOT_FOUND"}`

### Requirement: Patient Consumes Prescription

The system MUST allow patients to mark prescriptions as consumed via `PUT /api/prescriptions/{id}/consume`, validated by `ConsumePrescriptionRequest`. Only `pending`→`consumed` transition is permitted. On success, `consumed_at` SHALL be set. Response SHALL use `PrescriptionResource`. Authorization: `PrescriptionPolicy::consume`.
(Previously: inline validation and raw model arrays.)

#### Scenario: Patient consumes own pending prescription

- GIVEN an authenticated patient with pending prescription #1
- WHEN PUT `/api/prescriptions/1/consume`
- THEN 200 with `PrescriptionResource`, `status: "consumed"`, and `consumed_at` set to now

#### Scenario: Already consumed returns conflict

- GIVEN an authenticated patient with already-consumed prescription #1
- WHEN PUT `/api/prescriptions/1/consume`
- THEN 409 with `{message, code: "CONFLICT"}`

#### Scenario: Non-owner blocked

- GIVEN an authenticated patient who does NOT own prescription #5
- WHEN PUT `/api/prescriptions/5/consume`
- THEN 404 with `code: "NOT_FOUND"`

### Requirement: Patient Lists Own Prescriptions

The system MUST allow patients to list their prescriptions via `GET /api/me/prescriptions`, validated by `PrescriptionFilterRequest`. Results SHALL be paginated, ordered by `created_at DESC`. Response SHALL use `PrescriptionResource` collection. Protected by `auth:sanctum` and `role:patient`.
(Previously: inline validation and raw model arrays.)

#### Scenario: Patient lists prescriptions

- GIVEN an authenticated patient with prescriptions
- WHEN GET `/api/me/prescriptions`
- THEN 200 with `PrescriptionResource` collection (no items), own prescriptions only

#### Scenario: Filter by status

- GIVEN an authenticated patient with pending and consumed prescriptions
- WHEN GET `/api/me/prescriptions?status=consumed`
- THEN 200 with only consumed prescriptions
