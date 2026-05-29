# Exploration: Batch 4 — Requests + Resources + Error Format + Patient Search

## Executive Summary

Batch 4 refactors the existing inline validation and raw `->toArray()` responses into proper Laravel Form Requests, API Resources, and a centralized error format. It also introduces the patient search endpoint (`GET /api/patients`) that doctors need to find patients when creating prescriptions. The scope is **backward-compatible** — existing routes and behavior remain, only the internal implementation and response shape change.

## Current State

### Validation
All validation is inline via `$request->validate()` in two controllers:
- **AuthController::login** — 2 rules (email required, password required)
- **PrescriptionController::store** — 8 rules including nested `items.*` validation
- **PrescriptionController::index** — 3 filter rules (status, from, to)
- **PrescriptionController::myPrescriptions** — 1 filter rule (status)

No Form Requests exist. `app/Http/Requests/` directory is empty.

### Response Format
All endpoints return raw model data via `$prescription->toArray()` or `$prescriptions` (paginator). This exposes:
- Internal model structure (timestamps, foreign keys)
- No controlled serialization
- No inclusion of related data in list views (items not eager-loaded in index)
- Inconsistent shapes between `store` (with items via service `load('items')`) and `index` (no items)

### Error Handling
- `bootstrap/app.php` only configures `shouldRenderJsonWhen` for `api/*`
- No custom exception handler
- Laravel default error responses: 422 returns `{message, errors}`, 401 returns `{message}`, 403 returns `{message}`
- The required format from `docs/requeriments.md` section 7 is `{message, code, details}`
- `PrescriptionService::consumePrescription` throws `HttpResponseException` with ad-hoc JSON (409)

### Routes & Middleware
- Role middleware uses Spatie's `RoleMiddleware` aliased as `role`
- Multiple roles via pipe: `role:admin|doctor` (confirmed working in tests)
- No `PatientController` or `DoctorController` exists

### Models
- `User` — has `doctor()` and `patient()` HasOne relations, uses `HasRoles` from Spatie
- `Patient` — belongsTo `User`, hasMany `Prescription`, has `birth_date` cast
- `Doctor` — belongsTo `User`, hasMany `Prescription`
- `Prescription` — belongsTo `Doctor` and `Patient`, hasMany `PrescriptionItem`, auto-generates UUID code
- `PrescriptionItem` — belongsTo `Prescription`, no special casts

### Tests
- 18 tests in `RbacAuthTest.php` covering auth, roles, middleware
- No prescription-specific tests yet (Batch 8)
- Pest 4 with `RefreshDatabase`

## Affected Areas

| File | Why |
|------|-----|
| `app/Http/Controllers/AuthController.php` | Replace inline validation with `LoginRequest`, return `UserResource` |
| `app/Http/Controllers/PrescriptionController.php` | Replace inline validation with 3 Form Requests, return Resources |
| `bootstrap/app.php` | Add custom exception handler for standardized error format |
| `routes/api.php` | Add `GET /api/patients` route with `role:admin|doctor` |
| `app/Http/Controllers/PatientController.php` | **NEW** — patient search/list endpoint |
| `app/Http/Requests/Auth/LoginRequest.php` | **NEW** |
| `app/Http/Requests/Prescriptions/StorePrescriptionRequest.php` | **NEW** |
| `app/Http/Requests/Prescriptions/PrescriptionFilterRequest.php` | **NEW** |
| `app/Http/Requests/Prescriptions/ConsumePrescriptionRequest.php` | **NEW** |
| `app/Http/Resources/UserResource.php` | **NEW** |
| `app/Http/Resources/PatientResource.php` | **NEW** |
| `app/Http/Resources/PrescriptionResource.php` | **NEW** |
| `app/Http/Resources/PrescriptionItemResource.php` | **NEW** |
| `app/Exceptions/Handler.php` or `bootstrap/app.php` closure | **NEW** — centralized error handling |
| `tests/Feature/RbacAuthTest.php` | Update assertions for new response shapes |

## Architecture Decisions

### 1. Form Request Directory Structure

**Decision: Domain-subdirectory structure** (matching `docs/requeriments.md` section 10)

```
app/Http/Requests/
  Auth/
    LoginRequest.php
  Prescriptions/
    StorePrescriptionRequest.php
    PrescriptionFilterRequest.php
    ConsumePrescriptionRequest.php
```

**Rationale:** The requirements doc explicitly shows this structure. It scales well when we add `Users/StoreUserRequest.php` in Batch 6. Flat structure would become unwieldy with 10+ requests.

### 2. Nested `items.*` Validation in StorePrescriptionRequest

**Decision: Use Laravel's array validation syntax directly in `rules()` method**

```php
public function rules(): array
{
    return [
        'patient_id' => ['required', 'exists:patients,id'],
        'notes' => ['nullable', 'string', 'max:1000'],
        'items' => ['required', 'array', 'min:1'],
        'items.*.name' => ['required', 'string', 'max:255'],
        'items.*.dosage' => ['nullable', 'string', 'max:255'],
        'items.*.quantity' => ['required', 'integer', 'min:1'],
        'items.*.instructions' => ['nullable', 'string', 'max:1000'],
    ];
}
```

**Rationale:** This is the standard Laravel approach, identical to the current inline validation. No custom validation logic needed. The `PrescriptionService` already handles the array structure correctly.

### 3. Resource Structure & Relationships

**Decision: Resources include relationships via `whenLoaded()` pattern**

| Resource | Includes | Notes |
|----------|----------|-------|
| `UserResource` | id, name, email, role (via `getRoleNames()->first()`) | Used in login + profile |
| `PatientResource` | id, user (name, email), birth_date | For patient search results |
| `PrescriptionResource` | id, code, status, notes, consumed_at, created_at, patient (via PatientResource), items (via PrescriptionItemResource when loaded) | Conditional items — loaded in `show`, not in `index` |
| `PrescriptionItemResource` | id, name, dosage, quantity, instructions | Simple flat resource |

**Rationale:** `whenLoaded()` prevents N+1 queries in list views while including full data in detail views. The `PrescriptionResource` will check `$this->resource->relationLoaded('items')` before including items.

For the `role` field in `UserResource`, we'll call `$this->getRoleNames()->first()` — same pattern currently used in `AuthController`. This is acceptable for a single-role system.

### 4. Exception Handler Approach

**Decision: Use `bootstrap/app.php` `withExceptions()` closure (Laravel 11+ style)**

```php
->withExceptions(function (Exceptions $exceptions): void {
    $exceptions->shouldRenderJsonWhen(fn ($r) => $r->is('api/*'));
    $exceptions->render(function (Throwable $e, Request $request) {
        // Map exceptions to standardized format
    });
})
```

**Rationale:** Laravel 11+ removed the `App\Exceptions\Handler` class in favor of the fluent configuration in `bootstrap/app.php`. Creating a separate handler class would be fighting the framework. The closure approach is cleaner and matches the existing codebase pattern.

Error mapping:
- `AuthenticationException` → 401 `{message: "Unauthenticated", code: "UNAUTHORIZED"}`
- `AuthorizationException` → 403 `{message: "Forbidden", code: "FORBIDDEN"}`
- `ModelNotFoundException` → 404 `{message: "Not Found", code: "NOT_FOUND"}`
- `ValidationException` → 422 `{message: "Validation failed", code: "VALIDATION_ERROR", details: {errors: {...}}}`
- `HttpResponseException` (from PrescriptionService) → pass through with standardized wrapper
- Default → 500 `{message: "Server Error", code: "SERVER_ERROR"}` (only in debug=false)

### 5. Patient Search Endpoint

**Decision: LIKE query on `users.name` via Patient → User relationship**

```
GET /api/patients?query=&page=&limit=
```

Implementation:
- Search `patients` table, join `users` for name
- `WHERE users.name LIKE %query%`
- Paginated (default 15, max 100 — same convention as prescriptions)
- Ordered by `users.name ASC`
- Returns `PatientResource` collection

**Rationale:** The requirements say "buscar pacientes" (search patients). The patient's name lives in the `users` table (via `user_id`). A LIKE query on name is the simplest and most useful search for a doctor selecting a patient. We search on `users.name` because that's the display field.

Route: `GET /api/patients` under `middleware(['auth:sanctum', 'role:admin|doctor'])` — matching the existing `role:admin|doctor` pattern confirmed in routes and tests.

### 6. Backward Compatibility Strategy

**Decision: Breaking response shapes are acceptable within the same batch**

The response format changes from raw `->toArray()` to API Resources. This is a **controlled breaking change** — the frontend hasn't been built yet (Batch 4 is still backend-only). The API contract from `docs/requeriments.md` section 5 defines the expected shapes, and Resources will enforce those shapes consistently.

Existing tests that assert `->toArray()` structure will need updating to match Resource output. This is intentional — tests should validate the contract, not the internal representation.

## Approaches Comparison

### Exception Handler

| Approach | Pros | Cons | Complexity |
|----------|------|------|------------|
| `bootstrap/app.php` closure (recommended) | Matches Laravel 11+ convention, no extra file, consistent with existing code | Slightly harder to test in isolation | Low |
| Custom `App\Exceptions\Handler` class | Easier to unit test, more explicit | Fights Laravel 11+ direction, requires registration | Medium |
| Middleware-based error wrapping | Can intercept before framework handles | Duplicates framework logic, fragile | High |

### Patient Search

| Approach | Pros | Cons | Complexity |
|----------|------|------|------------|
| LIKE on `users.name` (recommended) | Simple, fast for small datasets, matches user expectations | No full-text search, limited to name only | Low |
| Full-text search on multiple fields | More powerful, searches email too | Overkill for MVP, requires index setup | Medium |
| Separate search service | Extensible, can add fuzzy matching later | Unnecessary abstraction now | High |

## Risks

1. **Response shape changes break existing tests** — The `RbacAuthTest.php` tests assert specific JSON structures from login/profile. These will need updating to match `UserResource` output. Mitigation: update tests as part of the same batch.

2. **`getRoleNames()->first()` in UserResource** — This triggers a database query per resource instance. In paginated lists (100 users), this is 100 extra queries. Mitigation: For list endpoints, eager-load roles or use a different approach. For now, `UserResource` is only used for single-user responses (login, profile), so N+1 is not an issue yet.

3. **Error handler catches too broadly** — If the `render()` closure catches all `Throwable`, it might swallow framework-level errors. Mitigation: Only handle specific exception types, let others fall through to Laravel's default handler.

4. **Patient search LIKE query performance** — With no index on `users.name`, large datasets will be slow. Mitigation: Acceptable for MVP scale. Add index in a future batch if needed.

5. **PrescriptionResource conditional items** — If a caller forgets to `load('items')`, the resource silently omits them. This is by design but could confuse API consumers. Mitigation: Document the behavior; the `show` endpoint explicitly loads items.

## Scope Summary

| Deliverable | Files | Effort |
|-------------|-------|--------|
| 4 Form Requests | 4 new files | Low |
| 4 API Resources | 4 new files | Low |
| Error standardization | Modify `bootstrap/app.php` | Low |
| PatientController + route | 1 new controller, 1 route addition | Low |
| Test updates | Update existing tests | Medium |
| **Total** | **~10 new files, 2 modified** | **Medium** |

## Ready for Proposal

**Yes.** The exploration is complete with clear decisions on all architecture questions. The scope is well-bounded, risks are manageable, and the approach is consistent with Laravel conventions and the existing codebase patterns. The orchestrator should proceed to `sdd-propose` or `sdd-spec`.
