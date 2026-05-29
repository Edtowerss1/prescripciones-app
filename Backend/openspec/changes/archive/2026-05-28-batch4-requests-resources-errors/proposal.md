# Proposal: Batch 4 — Requests, Resources, and Consistent API Errors

## Intent

Remove inline validation and raw model serialization so the API exposes stable contracts, consistent error envelopes, and a searchable patient list for doctors/admins.

## Scope

### In Scope
- Add 4 Form Requests under `app/Http/Requests/Auth` and `app/Http/Requests/Prescriptions`
- Add 4 API Resources under `app/Http/Resources`
- Standardize API exceptions in `bootstrap/app.php` to `{message, code, details}`
- Add `GET /api/patients` for admin/doctor search
- Refactor `AuthController` and `PrescriptionController` to use the new contracts

### Out of Scope
- PDF generation (Batch 5)
- Admin endpoints (Batch 6)
- Seeders (Batch 7)
- Tests (Batch 8)
- `DoctorController`

## Capabilities

### New Capabilities
- `patient-search`: searchable `/api/patients` listing for admin/doctor access

### Modified Capabilities
- `api-foundation`: API exceptions MUST render as `{message, code, details}`
- `api-authentication`: login/profile responses MUST use `UserResource`
- `prescription-lifecycle`: prescription endpoints MUST use Form Requests and Resources for consistent payloads

## Approach

Use domain subdirectories for requests, `whenLoaded()` in resources to avoid N+1 queries, and a `withExceptions()` closure in `bootstrap/app.php` to normalize common API exceptions. Implement patient search with `users.name LIKE %query%`, paginated and role-protected.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `app/Http/Controllers/AuthController.php` | Modified | Swap inline login validation for `LoginRequest` and return `UserResource` |
| `app/Http/Controllers/PrescriptionController.php` | Modified | Use requests/resources for store, index, show, and consume flows |
| `app/Http/Controllers/PatientController.php` | New | Search patients by name for admin/doctor |
| `bootstrap/app.php` | Modified | Centralize API exception rendering |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Response-shape breakage | Medium | Backend-only change; update consumers after this batch |
| Error mapping misses cases | Medium | Let unhandled exceptions fall back to Laravel defaults |
| Patient search performance | Low | Acceptable MVP LIKE query; optimize later if needed |

## Rollback Plan

Revert controller/request/resource wiring, remove the patient route/controller, and restore inline validation plus default exception rendering.

## Dependencies

- Existing auth/RBAC/prescription flows from Batches 1-3
- Laravel 13 exception customization in `bootstrap/app.php`

## Success Criteria

- Auth and prescription endpoints no longer use inline validation or raw model arrays
- API errors follow the standardized envelope
- Admin/doctor can search patients via `/api/patients`
