# Tasks: Core Prescriptions (Batch 3)

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~280 |
| 400-line budget risk | Low |
| Chained PRs recommended | No |
| Suggested split | Single PR |
| Delivery strategy | ask-on-risk |

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: size-exception
400-line budget risk: Low

## Phase 1: Schema Fix

- [x] 1.1 Create `database/migrations/2026_05_28_000001_fix_prescriptions_schema.php` — add `notes` (text, nullable) and `consumed_at` (timestamp, nullable) after `status` on `prescriptions`; drop single `status` index and add composite `(status, created_at)`; rename `medication_name` → `name` on `prescription_items`; add `quantity` (integer) after `name`
- [x] 1.2 Update `app/Models/Prescription.php` — add `'notes'` to `#[Fillable]`; add `'consumed_at' => 'datetime'` to `casts()`
- [x] 1.3 Update `app/Models/PrescriptionItem.php` — change `#[Fillable]` from `medication_name` to `name`; add `'quantity'`
- [x] 1.4 Update `database/factories/PrescriptionItemFactory.php` — rename `medication_name` → `name`; add `'quantity' => fake()->numberBetween(1, 100)`

## Phase 2: Service Layer

- [x] 2.1 Create `app/Services/PrescriptionService.php` with `createPrescription(Doctor, Patient, ?string $notes, array $items): Prescription` — wraps `DB::transaction()`, creates Prescription + items, returns model with `->load('items')`, throws `HttpResponseException` on already-consumed
- [x] 2.2 Add `consumePrescription(Prescription): Prescription` — guards `status === 'pending'` (throws `HttpResponseException` with 409 otherwise), sets `status='consumed'` + `consumed_at=now()`, saves and returns

## Phase 3: Authorization

- [x] 3.1 Create `app/Policies/PrescriptionPolicy.php` with `create(User): bool` — returns `$user->hasRole('doctor')`
- [x] 3.2 Add `view(User, Prescription): bool` — returns true for admin, doctor-owner (`$prescription->doctor->user_id === $user->id`), or patient-owner (`$prescription->patient->user_id === $user->id`)
- [x] 3.3 Add `consume(User, Prescription): bool` — returns true only for patient-owner

## Phase 4: Controller + Routes

- [x] 4.1 Create `app/Http/Controllers/PrescriptionController.php` with `store(Request, PrescriptionService)` — inline `$request->validate()`, `$this->authorize('create')`, delegates to service, returns `response()->json(..., 201)`
- [x] 4.2 Add `index(Request)` — validates optional `status|from|to` filters, scopes to `$request->user()->doctor->prescriptions()`, paginates (default 15, max 100), ordered `created_at DESC`
- [x] 4.3 Add `show(Prescription)` — `$this->authorize('view', $prescription)`, eager-loads `->load('items')`, returns JSON
- [x] 4.4 Add `consume(Prescription, PrescriptionService)` — `$this->authorize('consume', $prescription)`, delegates to service, returns updated JSON
- [x] 4.5 Add `myPrescriptions(Request)` — scoped to `$request->user()->patient->prescriptions()`, filterable by `status`, paginated
- [x] 4.6 Register routes in `routes/api.php` — 3 groups: doctor-only (`POST/GET /api/prescriptions`), auth-only (`GET /api/prescriptions/{prescription}`, `PUT /api/prescriptions/{prescription}/consume`), patient-only (`GET /api/me/prescriptions`)
