# Exploration: batch3-core-prescriptions

## Executive Summary

Batch 3 builds the core prescription lifecycle: doctor creates/list/detail, patient consume/list, with filters, pagination, and policies. Requires 1 schema migration to fix Batch 2 gaps, 1 new controller (`PrescriptionController`), 1 policy (`PrescriptionPolicy`), and 1 service (`PrescriptionService`). Inline validation is sufficient for this batch (Form Requests deferred to Batch 4). Estimated ~250-350 lines of new PHP code.

## Current State

### What exists (Batches 1-2)
- **Auth**: Sanctum login/logout/profile at `/api/auth/*` (AuthController)
- **RBAC**: Spatie permission with `role` middleware alias registered in `bootstrap/app.php`
- **Models**: User, Doctor, Patient, Prescription, PrescriptionItem — all with relationships and factories
- **Routes**: Only auth routes + 2 test routes (`/admin-only`, `/admin-or-doctor`)
- **No controllers** for prescriptions, **no policies**, **no services**, **no Form Requests**, **no API Resources**

### Schema gaps vs `requeriments.md`

| Table | Current | Required | Action |
|-------|---------|----------|--------|
| `prescriptions` | Missing `notes` (text, nullable) | `notes` text nullable | Migration: add column |
| `prescriptions` | Missing `consumed_at` (timestamp, nullable) | `consumed_at` timestamp nullable | Migration: add column |
| `prescriptions` | Has `status` index (single) | Composite `(status, created_at)` | Migration: drop single, add composite |
| `prescription_items` | Column `medication_name` | Column `name` | Migration: rename column |
| `prescription_items` | Missing `quantity` | `quantity` integer | Migration: add column |
| `doctors` | Has `license_number` (extra) | Not in requirements | **Keep** — harmless extension |

### Model gaps

| Model | Current `#[Fillable]` | Needed for Batch 3 |
|-------|----------------------|-------------------|
| Prescription | `['doctor_id', 'patient_id', 'status']` | Add `'notes'` |
| PrescriptionItem | `['prescription_id', 'medication_name', 'dosage', 'instructions']` | Rename to `'name'`, add `'quantity'` |

## Scope Definition (Batch 3)

### In scope
1. **Schema migration** — fix 5 gaps listed above
2. **Model updates** — fillable + casts for new columns
3. **Factory updates** — PrescriptionItemFactory uses `name` instead of `medication_name`, add `quantity`
4. **PrescriptionController** — 5 endpoints:
   - `POST /api/prescriptions` (doctor only)
   - `GET /api/prescriptions` (doctor only, filtered, paginated)
   - `GET /api/prescriptions/{id}` (owner doctor, owner patient, admin)
   - `PUT /api/prescriptions/{id}/consume` (owner patient only)
   - `GET /api/me/prescriptions` (patient only, paginated)
5. **PrescriptionPolicy** — view, consume, create authorization
6. **PrescriptionService** — creation with items (transactional), consume logic
7. **Route definitions** in `api.php`

### Out of scope (per backend-batches.md)
- Form Request classes → Batch 4
- API Resource classes → Batch 4
- Standard error format → Batch 4
- PDF generation → Batch 5
- Admin metrics → Batch 6
- Seeders → Batch 7
- Tests → Batch 8

## Endpoint Inventory

| Method | Route | Role | Description |
|--------|-------|------|-------------|
| POST | `/api/prescriptions` | doctor | Create prescription with items |
| GET | `/api/prescriptions` | doctor | List own prescriptions (status, from, to filters) |
| GET | `/api/prescriptions/{id}` | doctor/patient/admin | Detail (policy-gated) |
| PUT | `/api/prescriptions/{id}/consume` | patient | Mark as consumed (policy-gated) |
| GET | `/api/me/prescriptions` | patient | List own prescriptions (status filter) |

## Architecture Decisions

### 1. Controller vs Service Layer
**Decision**: Use a thin controller + `PrescriptionService` for creation and consume.

**Rationale**: Creating a prescription with items is a transactional operation (create prescription + multiple items). This belongs in a service. The controller should only handle request parsing, validation, and response formatting. This keeps controllers under 10 lines (per Laravel best practices) and isolates business logic.

**Service methods**:
- `createPrescription(Doctor $doctor, Patient $patient, ?string $notes, array $items): Prescription`
- `consumePrescription(Prescription $prescription): Prescription`

### 2. Validation approach
**Decision**: Inline validation in controller for Batch 3, Form Requests in Batch 4.

**Rationale**: Batch 4 explicitly covers Form Requests. Inline `$request->validate()` is functional and allows Batch 3 to deliver working endpoints. The validation rules will be extracted to Form Request classes in Batch 4 without changing controller logic.

**Validation rules needed**:
```php
// POST /api/prescriptions
[
    'patient_id' => ['required', 'integer', 'exists:patients,id'],
    'notes' => ['nullable', 'string', 'max:1000'],
    'items' => ['required', 'array', 'min:1'],
    'items.*.name' => ['required', 'string', 'max:255'],
    'items.*.dosage' => ['nullable', 'string', 'max:255'],
    'items.*.quantity' => ['required', 'integer', 'min:1'],
    'items.*.instructions' => ['nullable', 'string', 'max:1000'],
]

// GET filters
[
    'status' => ['nullable', 'in:pending,consumed'],
    'from' => ['nullable', 'date'],
    'to' => ['nullable', 'date', 'after_or_equal:from'],
]

// PUT consume — no body needed, just authorization
```

### 3. Policy design
**Decision**: `PrescriptionPolicy` with 3 methods: `view`, `consume`, `create`.

```php
class PrescriptionPolicy
{
    // Any authenticated user can create (role check at route level)
    public function create(User $user): bool {
        return $user->hasRole('doctor');
    }

    // View: owner doctor, owner patient, or admin
    public function view(User $user, Prescription $prescription): bool {
        return $user->hasRole('admin')
            || $prescription->doctor->user_id === $user->id
            || $prescription->patient->user_id === $user->id;
    }

    // Consume: only the patient who owns the prescription
    public function consume(User $user, Prescription $prescription): bool {
        return $prescription->patient->user_id === $user->id;
    }
}
```

**Registration**: Auto-discovery via `AuthServiceProvider` or explicit registration in `boot()`. Laravel 13 auto-discovers policies in `app/Policies/` by convention.

### 4. Response format
**Decision**: Return raw JSON arrays for Batch 3, API Resources in Batch 4.

The responses will be simple arrays matching the model structure. Batch 4 will wrap these in API Resources for consistency.

**POST create response** (201):
```json
{
  "id": 1,
  "code": "uuid-string",
  "status": "pending",
  "notes": "...",
  "doctor_id": 1,
  "patient_id": 2,
  "consumed_at": null,
  "created_at": "...",
  "items": [...]
}
```

**GET list response** (200): Paginated Laravel default format.

### 5. Route structure
**Decision**: Group routes by role middleware, use explicit controller methods.

```php
// Doctor routes
Route::middleware(['auth:sanctum', 'role:doctor'])->group(function () {
    Route::post('/prescriptions', [PrescriptionController::class, 'store']);
    Route::get('/prescriptions', [PrescriptionController::class, 'index']);
});

// Shared detail route (policy-gated)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/prescriptions/{prescription}', [PrescriptionController::class, 'show']);
    Route::put('/prescriptions/{prescription}/consume', [PrescriptionController::class, 'consume']);
});

// Patient routes
Route::middleware(['auth:sanctum', 'role:patient'])->group(function () {
    Route::get('/me/prescriptions', [PrescriptionController::class, 'myPrescriptions']);
});
```

**Note**: `show` and `consume` are policy-gated rather than role-gated because both doctor and patient (and admin) can view, but only the owning patient can consume. The policy handles the fine-grained check.

### 6. Implicit Route Model Binding
**Decision**: Use implicit binding for `{prescription}` in `show` and `consume`.

The route parameter `{prescription}` will automatically resolve to a `Prescription` model instance. The policy then checks authorization. This is the Laravel convention and keeps the controller clean.

### 7. Pagination
**Decision**: Use Laravel's `paginate()` with configurable per-page.

- Default: `paginate(15)` (Laravel default)
- Respect `limit` query param if provided, cap at 100
- Order by `created_at DESC` (per requirements)

## Affected Files

| File | Action | Reason |
|------|--------|--------|
| `database/migrations/*_fix_prescriptions_schema.php` | Create | Add missing columns, rename, add composite index |
| `app/Models/Prescription.php` | Update | Add `notes` to fillable |
| `app/Models/PrescriptionItem.php` | Update | Rename fillable `medication_name` → `name`, add `quantity` |
| `database/factories/PrescriptionItemFactory.php` | Update | Use `name` instead of `medication_name`, add `quantity` |
| `routes/api.php` | Update | Add prescription routes |
| `app/Http/Controllers/PrescriptionController.php` | Create | 5 endpoint methods |
| `app/Services/PrescriptionService.php` | Create | Transactional create + consume |
| `app/Policies/PrescriptionPolicy.php` | Create | view, consume, create authorization |

## Risk Analysis

| Risk | Impact | Mitigation |
|------|--------|------------|
| **Column rename `medication_name` → `name`** | Medium — could break if any code references old column | Only factory references it; migration handles rename cleanly |
| **No Form Requests yet** | Low — inline validation works, but less reusable | Explicitly deferred to Batch 4; rules are well-defined |
| **No API Resources yet** | Low — raw JSON is functional | Batch 4 will add Resources without changing endpoints |
| **Policy + middleware overlap** | Low — middleware handles role, policy handles ownership | Clear separation: route = "can this role access?", policy = "can this user access this resource?" |
| **Transaction rollback on item creation failure** | Medium — partial prescription without items | Service wraps in `DB::transaction()` |
| **Missing tests (Batch 8)** | High — no automated verification until Batch 8 | Manual testing required; Batch 3 should be tested manually before merge |
| **Composite index replaces single status index** | Low — composite index `(status, created_at)` covers status-only queries too | PostgreSQL can use leftmost prefix of composite index |

## Dependencies

- **Batch 1** (Auth + RBAC) ✅ — required for role middleware and user resolution
- **Batch 2** (Models + Migrations) ✅ — required for Prescription/PrescriptionItem models
- **Batch 3** has no dependency on Batches 4-8

## Next Recommended Phase

**sdd-propose** — Create the change proposal with exact scope, approach, and rollback plan. The exploration is complete and all decisions are documented.

Alternatively, skip proposal and go directly to **sdd-spec** if the team wants to define the delta spec first.

## Ready for Proposal

**Yes.** All questions resolved:
- Schema gaps identified and migration strategy defined
- Controller/service/policy architecture decided
- Validation approach (inline → Form Requests in Batch 4) agreed
- Route structure designed
- Risk analysis complete
- File inventory complete
