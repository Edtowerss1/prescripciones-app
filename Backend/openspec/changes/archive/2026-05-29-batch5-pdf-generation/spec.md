# Delta for prescription-lifecycle

## ADDED Requirements

| # | Requirement | Strength |
|---|------------|----------|
| R1 | PDF Download Endpoint | MUST |
| R2 | PDF Content | MUST |
| R3 | Authorization | SHALL |
| R4 | Not Found | MUST |

### Requirement R1: PDF Download Endpoint

The system MUST expose `GET /api/prescriptions/{id}/pdf`, protected by `auth:sanctum`. The response SHALL force download as `prescription-{code}.pdf` with `Content-Type: application/pdf`. Authorization SHALL reuse `PrescriptionPolicy::view` — identical to `GET /api/prescriptions/{id}` (`Prescription Detail` requirement).

#### Scenario: Doctor owner downloads PDF

- GIVEN an authenticated doctor who created prescription `#1`
- WHEN GET `/api/prescriptions/1/pdf`
- THEN status 200, `Content-Type: application/pdf`
- AND `Content-Disposition` header SHALL contain `attachment; filename="prescription-{code}.pdf"`

#### Scenario: Patient owner downloads PDF

- GIVEN an authenticated patient assigned to prescription `#1`
- WHEN GET `/api/prescriptions/1/pdf`
- THEN status 200, `Content-Type: application/pdf`
- AND response body SHALL be non-empty PDF binary

#### Scenario: Non-owner receives 404

- GIVEN an authenticated doctor who did NOT create prescription `#5`
- WHEN GET `/api/prescriptions/5/pdf`
- THEN 404 with `{message, code: "NOT_FOUND"}`
- AND the response SHALL NOT indicate whether prescription exists

#### Scenario: Non-existent prescription returns 404

- GIVEN an authenticated user (any role)
- WHEN GET `/api/prescriptions/99999/pdf`
- THEN 404 with `{message, code: "NOT_FOUND"}` via implicit route model binding

### Requirement R2: PDF Content

The generated PDF MUST render the following fields from the prescription model:

| Field | Source |
|-------|--------|
| Prescription code | `$prescription->code` |
| Creation date | `$prescription->created_at` (formatted) |
| Status | `$prescription->status` |
| Patient name | `$prescription->patient->user->name` |
| Doctor name | `$prescription->doctor->user->name` |
| Doctor specialty | `$prescription->doctor->specialty` |
| Notes | `$prescription->notes` (if present) |
| Items table | `name`, `dosage`, `quantity`, `instructions` per item |

Relationships SHALL be eager-loaded (`items`, `doctor.user`, `patient.user`) to avoid N+1 queries.

#### Scenario: PDF contains all required fields

- GIVEN a prescription with items, doctor, and patient loaded
- WHEN `PdfService::generatePrescriptionPdf($prescription)` renders the Blade view
- THEN the rendered HTML SHALL contain `$prescription->code`, `$prescription->status`, `$prescription->created_at` (formatted), `$prescription->notes`
- AND SHALL contain `$prescription->patient->user->name` and `$prescription->doctor->user->name` with specialty
- AND the items table SHALL list `name`, `dosage`, `quantity`, and `instructions` for every item

### Requirement R3: Authorization

Authorization for the PDF endpoint SHALL be identical to `PrescriptionDetail` (`PrescriptionPolicy::view`): admin, doctor-owner, or patient-owner. The gateway SHALL use `Gate::allows('view', $prescription)` with `abort(404)` on denial — matching the existing pattern so PDF existence is not leaked to unauthorized users.

Covered by scenarios under R1 (Doctor owner, Patient owner, Non-owner 404).

### Requirement R4: Not Found

Implicit route model binding SHALL return 404 for non-existent prescription IDs, with the standard `{message, code: "NOT_FOUND"}` error body — consistent with all other `prescription-lifecycle` endpoints.

Covered by scenario under R1 (Non-existent prescription returns 404).
