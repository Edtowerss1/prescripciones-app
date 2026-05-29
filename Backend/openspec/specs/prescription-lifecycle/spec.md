# prescription-lifecycle Specification

## Purpose

Core prescription lifecycle: doctor creates/manages prescriptions with items, patient views and marks them consumed. Ownership-based authorization via `PrescriptionPolicy`.

## Dependencies

- `api-authentication` — Sanctum bearer tokens and protected route enforcement.
- `rbac-auth` — Spatie role middleware (`role:doctor`, `role:patient`) and `HasRoles` trait.

## Requirements

### Requirement: Schema Alignment

The `prescriptions` table MUST include `notes` (text, nullable), `consumed_at` (timestamp, nullable), and a composite index on `(status, created_at)`. The `prescription_items` table MUST rename `medication_name` to `name` and include `quantity` (integer).

#### Scenario: Schema matches requirements

- GIVEN the Batch 3 migration has run
- WHEN the database schema is inspected
- THEN `prescriptions` SHALL have columns `notes` and `consumed_at`
- AND `prescription_items` SHALL have `name` (not `medication_name`) and `quantity`
- AND a composite index `(status, created_at)` SHALL exist on `prescriptions`

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

### Requirement: Doctor Creates Prescription

The system MUST allow doctors to create prescriptions via `POST /api/prescriptions`, validated by `StorePrescriptionRequest` with nested `items.*` rules (name required|string|max:255, quantity required|integer|min:1, dosage nullable|string, instructions nullable|string). Response SHALL return `PrescriptionResource` with items loaded. Protected by `auth:sanctum` and `role:doctor`.

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

### Requirement: Prescription PDF Download

The system MUST provide a PDF download endpoint via `GET /api/prescriptions/{id}/pdf`, protected by `auth:sanctum`. The response SHALL force download as `prescription-{code}.pdf` with `Content-Type: application/pdf`. Authorization SHALL reuse `PrescriptionPolicy::view`, and unauthorized users MUST receive 404 without leaking whether the prescription exists. The generated PDF MUST render the prescription code, creation date, status, patient name, doctor name, doctor specialty, notes when present, and the items table. Relationships SHALL be eager-loaded (`items`, `doctor.user`, `patient.user`) to avoid N+1 queries.

#### Scenario: Doctor owner downloads PDF

- GIVEN an authenticated doctor who created prescription #1
- WHEN GET `/api/prescriptions/1/pdf`
- THEN 200 with `Content-Type: application/pdf`
- AND `Content-Disposition` SHALL contain `attachment; filename="prescription-{code}.pdf"`

#### Scenario: Patient owner downloads PDF

- GIVEN an authenticated patient assigned to prescription #1
- WHEN GET `/api/prescriptions/1/pdf`
- THEN 200 with `Content-Type: application/pdf`
- AND response body SHALL be non-empty PDF binary

#### Scenario: Non-owner gets 404

- GIVEN an authenticated doctor who did NOT create prescription #5
- WHEN GET `/api/prescriptions/5/pdf`
- THEN 404 with `{message, code: "NOT_FOUND"}`

#### Scenario: Non-existent prescription returns 404

- GIVEN an authenticated user (any role)
- WHEN GET `/api/prescriptions/99999/pdf`
- THEN 404 with `{message, code: "NOT_FOUND"}` via implicit route model binding

#### Scenario: PDF contains all required fields

- GIVEN a prescription with items, doctor, and patient loaded
- WHEN the PDF view is rendered
- THEN the rendered HTML SHALL contain the prescription code, status, created date, notes, patient name, doctor name, specialty, and every item's name, dosage, quantity, and instructions

### Requirement: Patient Consumes Prescription

The system MUST allow patients to mark prescriptions as consumed via `PUT /api/prescriptions/{id}/consume`, validated by `ConsumePrescriptionRequest`. Only `pending`→`consumed` transition is permitted. On success, `consumed_at` SHALL be set. Response SHALL use `PrescriptionResource`. Authorization: `PrescriptionPolicy::consume`.

#### Scenario: Patient consumes own pending prescription

- GIVEN an authenticated patient with pending prescription #1
- WHEN PUT `/api/prescriptions/1/consume`
- THEN 200 with `PrescriptionResource`, `status: "consumed"`, and `consumed_at` set to now

#### Scenario: Already consumed returns conflict

- GIVEN an authenticated patient with an already-consumed prescription #1
- WHEN PUT `/api/prescriptions/1/consume`
- THEN 409 with `{message, code: "CONFLICT"}`

#### Scenario: Non-owner blocked

- GIVEN an authenticated patient who does NOT own prescription #5
- WHEN PUT `/api/prescriptions/5/consume`
- THEN 404 with `code: "NOT_FOUND"`

### Requirement: Patient Lists Own Prescriptions

The system MUST allow patients to list their prescriptions via `GET /api/me/prescriptions`, validated by `PrescriptionFilterRequest`. Results SHALL be paginated, ordered by `created_at DESC`. Response SHALL use `PrescriptionResource` collection. Protected by `auth:sanctum` and `role:patient`.

#### Scenario: Patient lists prescriptions

- GIVEN an authenticated patient with prescriptions
- WHEN GET `/api/me/prescriptions`
- THEN 200 with `PrescriptionResource` collection (no items), own prescriptions only

#### Scenario: Filter by status

- GIVEN an authenticated patient with pending and consumed prescriptions
- WHEN GET `/api/me/prescriptions?status=consumed`
- THEN 200 with only consumed prescriptions

### Requirement: Prescription Policy

The application SHALL define `PrescriptionPolicy` with three abilities: `create` (doctor role only), `view` (admin OR doctor-owner OR patient-owner), and `consume` (patient-owner only). Ownership is determined by `prescription.doctor.user_id === $user->id` for doctors and `prescription.patient.user_id === $user->id` for patients.

#### Scenario: Doctor can create; patient cannot

- GIVEN a user with role `doctor`
- WHEN `PrescriptionPolicy::create` is evaluated
- THEN it SHALL return true
- AND for a user with role `patient` it SHALL return false

#### Scenario: Admin can view any prescription

- GIVEN a user with role `admin`
- WHEN `PrescriptionPolicy::view` is evaluated for any prescription
- THEN it SHALL return true

#### Scenario: Non-owner doctor cannot view

- GIVEN a doctor and a prescription created by a different doctor
- WHEN `PrescriptionPolicy::view` is evaluated
- THEN it SHALL return false

### Requirement: Transactional Creation

Prescription and items creation MUST be atomic. If any item fails, the entire operation SHALL roll back — no orphan prescription without items and no orphan items without prescription.

#### Scenario: All-or-nothing creation

- GIVEN a valid request with items
- WHEN the creation service executes
- THEN prescription and all items SHALL persist together
- AND a rollback SHALL occur if any item insert fails
