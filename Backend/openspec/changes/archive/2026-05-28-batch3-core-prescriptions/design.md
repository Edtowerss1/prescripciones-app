# Design: Core Prescriptions (Batch 3)

## Technical Approach

Thin controller (inline `$request->validate()`, ~10-line methods) delegating to `PrescriptionService` for transactional writes. `PrescriptionPolicy` gated by `$this->authorize()` in controller. Raw JSON responses via `response()->json()`. Implicit route model binding for `{prescription}` on `show`/`consume`. Column rename and composite index via a single migration step.

## Architecture Decisions

| Decision | Choice | Alternatives | Rationale |
|----------|--------|-------------|-----------|
| Business logic layer | `PrescriptionService` in `app/Services/` | Controller inline, Action classes | Transactional create + consume needs a shared boundary; service isolates `DB::transaction()` from HTTP layer |
| Validation | Inline `$request->validate()` | Form Request classes | Batch 4 defers Form Requests; inline rules are self-documenting in controller |
| Policy invocation | `AuthorizesRequests` trait on controller | Gate::authorize() in service, middleware-only | `$this->authorize('view', $model)` is the Laravel convention and maps cleanly to `PrescriptionPolicy` |
| Pagination limit param | Respect `limit` query param, capped at 100 | Hardcoded 15, no limit param | Exploration decision 7; mirrors common API convention |
| consume endpoint method | `PUT` (custom action) | `POST /consume`, `PATCH` with body | `PUT` on sub-resource is the most REST-like for an idempotent state transition |
| `consumed_at` default | `nullable`; set to `now()` on consume via service | Default `null` with observer, `Carbon` cast | Service sets it explicitly; adding a `datetime` cast in model `casts()` gives Eloquent auto-formatting |

## Data Flow

```
POST /api/prescriptions (doctor)
  Request ──→ PrescriptionController::store()
               │ $request->validate([patient_id, notes, items...])
               │ $doctor = $request->user()->doctor
               │ Gate::authorize('create', Prescription::class)
               │
               └──→ PrescriptionService::createPrescription($doctor, $patient, $notes, $items)
                     │ DB::transaction(function() {
                     │   $prescription = Prescription::create([...])
                     │   foreach ($items → PrescriptionItem::create([...]))
                     │   return $prescription->load('items')
                     │ })
                     │
               ←── $prescription (with items)
  Response ←── response()->json($prescription->toArray(), 201)


GET /api/prescriptions (doctor, filtered/paginated)
  Request ──→ PrescriptionController::index()
               │ $request->validate([status, from, to])  // optional filters
               │ $doctor = $request->user()->doctor
               │ limit = min($request->limit ?? 15, 100)
               │ Query: $doctor->prescriptions()
               │   → when status → filtered
               │   → when from/to → whereBetween('created_at')
               │   → orderByDesc('created_at') → paginate($limit)
               │
  Response ←── response()->json($paginator)


PUT /api/prescriptions/{prescription}/consume (patient-owned)
  Request ──→ PrescriptionController::consume($prescription)
               │ $this->authorize('consume', $prescription)   // Policy check
               │
               └──→ PrescriptionService::consumePrescription($prescription)
                     │ if $prescription->status !== 'pending' → throw HttpResponseException(409)
                     │ $prescription->update(['status' => 'consumed', 'consumed_at' => now()])
                     │ return $prescription
                     │
               │
  Response ←── response()->json($prescription->toArray())
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `database/migrations/*_fix_prescriptions_schema.php` | Create | Add `notes`/`consumed_at` to prescriptions, rename `medication_name`→`name` + add `quantity` to items, drop single status index + add composite `(status, created_at)` |
| `app/Models/Prescription.php` | Modify | Add `notes` to `#[Fillable]`, add `consumed_at` to `casts()` as `datetime` |
| `app/Models/PrescriptionItem.php` | Modify | `#[Fillable]`: rename `medication_name`→`name`, add `quantity` |
| `database/factories/PrescriptionItemFactory.php` | Modify | `medication_name`→`name`, add `quantity` field |
| `app/Http/Controllers/PrescriptionController.php` | Create | 5 methods: `store`, `index`, `show`, `consume`, `myPrescriptions` |
| `app/Services/PrescriptionService.php` | Create | `createPrescription()`, `consumePrescription()` |
| `app/Policies/PrescriptionPolicy.php` | Create | `create()`, `view()`, `consume()` |
| `routes/api.php` | Modify | Add 5 prescription routes with role middleware |

## Interfaces / Contracts

### PrescriptionPolicy (app/Policies/PrescriptionPolicy.php)

```php
class PrescriptionPolicy
{
    // Gate check: user is doctor
    public function create(User $user): bool

    // Gate check: admin, doctor-owner, or patient-owner
    public function view(User $user, Prescription $prescription): bool

    // Gate check: patient-owner only
    public function consume(User $user, Prescription $prescription): bool
}
```

### PrescriptionService (app/Services/PrescriptionService.php)

```php
class PrescriptionService
{
    // Creates prescription + items in a DB::transaction().
    // Returns the loaded model with items relationship.
    public function createPrescription(
        Doctor $doctor,
        Patient $patient,
        ?string $notes,
        array $items
    ): Prescription;

    // Transitions status pending→consumed, sets consumed_at.
    // Throws HttpResponseException with 409 if not pending.
    // Returns updated model.
    public function consumePrescription(Prescription $prescription): Prescription;
}
```

### PrescriptionController (app/Http/Controllers/PrescriptionController.php)

```php
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PrescriptionController extends Controller
{
    use AuthorizesRequests;

    // POST /api/prescriptions
    // Auth: doctor, validates: patient_id|notes|items.*
    public function store(Request $request, PrescriptionService $svc): JsonResponse

    // GET /api/prescriptions
    // Auth: doctor, filters: status|from|to, paginates
    public function index(Request $request): JsonResponse

    // GET /api/prescriptions/{prescription}
    // Auth: policy::view, loads items
    public function show(Prescription $prescription): JsonResponse

    // PUT /api/prescriptions/{prescription}/consume
    // Auth: policy::consume, delegates to PrescriptionService
    public function consume(Prescription $prescription, PrescriptionService $svc): JsonResponse

    // GET /api/me/prescriptions
    // Auth: patient, filters: status, paginates own
    public function myPrescriptions(Request $request): JsonResponse
}
```

### Route Definitions (routes/api.php)

```php
use App\Http\Controllers\PrescriptionController;

// Doctor-only: create + list own
Route::middleware(['auth:sanctum', 'role:doctor'])->group(function () {
    Route::post('/prescriptions', [PrescriptionController::class, 'store']);
    Route::get('/prescriptions', [PrescriptionController::class, 'index']);
});

// Authenticated (policy-gated): detail + consume
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/prescriptions/{prescription}', [PrescriptionController::class, 'show']);
    Route::put('/prescriptions/{prescription}/consume', [PrescriptionController::class, 'consume']);
});

// Patient-only: list own
Route::middleware(['auth:sanctum', 'role:patient'])->group(function () {
    Route::get('/me/prescriptions', [PrescriptionController::class, 'myPrescriptions']);
});
```

### Migration (database/migrations/*_fix_prescriptions_schema.php)

Prescriptions:
- `$table->text('notes')->nullable()` — after `status`
- `$table->timestamp('consumed_at')->nullable()` — after `notes`
- `$table->dropIndex(['status'])` + `$table->index(['status', 'created_at'])` — replaces single index

Prescription items:
- `$table->renameColumn('medication_name', 'name')`
- `$table->integer('quantity')` — after `name`

### Model Changes

**Prescription**: `#[Fillable]` adds `'notes'`. `casts()` adds `'consumed_at' => 'datetime'`.

**PrescriptionItem**: `#[Fillable]` becomes `['prescription_id', 'name', 'quantity', 'dosage', 'instructions']`.

### Factory Changes (PrescriptionItemFactory)

`'medication_name' => fake()->word()` → `'name' => fake()->word()`. Add `'quantity' => fake()->numberBetween(1, 100)`.

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Feature | Endpoint access control (403 for wrong role, 404 for non-owner) | Pest HTTP tests with actingAs + role |
| Feature | Prescription creation with items (201) | Seed patient, create as doctor, assert DB has both records |
| Feature | Consume transition: pending→consumed (200), consumed→consumed (409) | Create 2 prescriptions, consume first, attempt second |
| Unit | PrescriptionPolicy::view/consume/create gate logic | Direct policy method calls with model instances |

Tests deferred to Batch 8 per batch plan.

## Open Questions

None. All decisions resolved in exploration and locked in spec.

### Migration / Rollout

Single migration modifies existing schema. Rollback drops added columns, restores `medication_name` rename, and re-adds single `status` index. No data migration needed — new columns are nullable and the rename is transparent to Eloquent if fillable is updated in the same deploy.
