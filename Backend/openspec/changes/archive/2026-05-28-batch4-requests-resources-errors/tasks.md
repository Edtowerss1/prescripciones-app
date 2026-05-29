# Tasks: Batch 4 — Requests, Resources, and Consistent API Errors

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~340 |
| 400-line budget risk | Low |
| Chained PRs recommended | No |
| Suggested split | Single PR |
| Delivery strategy | ask-on-risk |

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: size-exception
400-line budget risk: Low

### Suggested Work Units

| Unit | Goal | Likely PR | Notes |
|------|------|-----------|-------|
| 1 | All phases | PR 1 → main | Within 400-line budget; single PR; no chaining needed |

## Phase 1: Form Requests

- [x] 1.1 Create `app/Http/Requests/Auth/LoginRequest.php` — `authorize()` true, `rules()`: email required|email, password required|string, `messages()` custom
- [x] 1.2 Create `app/Http/Requests/Prescriptions/StorePrescriptionRequest.php` — `authorize()` via `Gate: doctor`, `rules()`: patient_id exists:patients,id, items.* nested with min:1
- [x] 1.3 Create `app/Http/Requests/Prescriptions/PrescriptionFilterRequest.php` — `rules()`: status nullable|in:pending,consumed, from nullable|date, to nullable|date|after_or_equal:from
- [x] 1.4 Create `app/Http/Requests/Prescriptions/ConsumePrescriptionRequest.php` — `authorize()` delegates to `PrescriptionPolicy::consume`, `rules()` returns `[]`

## Phase 2: API Resources

- [x] 2.1 Create `app/Http/Resources/UserResource.php` — `toArray()`: `{id, name, email, role}` where role = `$this->getRoleNames()->first()`
- [x] 2.2 Create `app/Http/Resources/PatientResource.php` — `toArray()`: `{id, birth_date, user: {id,name,email}}` via `whenLoaded('user')`, omit `user_id`
- [x] 2.3 Create `app/Http/Resources/PrescriptionItemResource.php` — `toArray()`: `{id, name, dosage, quantity, instructions}`, omit `prescription_id`
- [x] 2.4 Create `app/Http/Resources/PrescriptionResource.php` — `toArray()`: `{id, code, status, notes, consumed_at, created_at}` + doctor/patient/items via `whenLoaded`, omit doctor_id/patient_id

## Phase 3: Error Handler & Service

- [x] 3.1 Add `render()` callbacks to `bootstrap/app.php` — map `AuthenticationException`→401/UNAUTHORIZED, `AuthorizationException`→403/FORBIDDEN, `ModelNotFoundException`→404/NOT_FOUND, `ValidationException`→422/VALIDATION_ERROR
- [x] 3.2 Update `app/Services/PrescriptionService.php` — add `'code' => 'CONFLICT'` to the `HttpResponseException` body in `consumePrescription()`

## Phase 4: Patient Search

- [x] 4.1 Create `app/Http/Controllers/PatientController.php` — `index()`: with('user') + whereHas LIKE search on users.name + paginate + PatientResource::collection
- [x] 4.2 Create `app/Policies/PatientPolicy.php` — `viewAny()` returns `$user->hasAnyRole(['admin', 'doctor'])`
- [x] 4.3 Add `GET /api/patients` to `routes/api.php` under `auth:sanctum` + `role:admin|doctor` middleware group

## Phase 5: Controller Refactors

- [x] 5.1 Refactor `AuthController::login` — inject `LoginRequest`, return `new UserResource($user)` + token
- [x] 5.2 Refactor `AuthController::profile` — return `new UserResource($request->user())`
- [x] 5.3 Refactor `PrescriptionController::store` — inject `StorePrescriptionRequest`, return `new PrescriptionResource()` with items loaded
- [x] 5.4 Refactor `PrescriptionController::index` — inject `PrescriptionFilterRequest`, return `PrescriptionResource::collection()`, use request validated data
- [x] 5.5 Refactor `PrescriptionController::show` — keep Gate::allows(404), return `new PrescriptionResource()` with items/doctor.user/patient.user loaded
- [x] 5.6 Refactor `PrescriptionController::consume` — inject `ConsumePrescriptionRequest`, return `new PrescriptionResource()`
- [x] 5.7 Refactor `PrescriptionController::myPrescriptions` — inject `PrescriptionFilterRequest`, return `PrescriptionResource::collection()`

## Phase 6: Test Updates

- [x] 6.1 Update `tests/Feature/RbacAuthTest.php` — `POST /api/auth/login` missing fields assertion: replace `assertJsonStructure(['message', 'errors'])` with `{message, code: "VALIDATION_ERROR", details: {errors: {...}}}`
- [x] 6.2 Add patient search feature test — search by name, no query, role enforcement, pagination
