# Design: Batch 4 — Requests, Resources, and Consistent API Errors

## Technical Approach

Replace inline `$request->validate()` and raw `->toArray()` with Form Requests and API Resources across `AuthController` and `PrescriptionController`. Add a centralized `withExceptions()->render()` closure in `bootstrap/app.php` mapping 5 exception types to `{message, code, details}`. Introduce `PatientController::index` with a `users.name LIKE %query%` search, paginated, protected by `role:admin|doctor`.

## Architecture Decisions

| Decision | Options | Choice | Rationale |
|----------|---------|--------|-----------|
| Request dir structure | Flat vs domain subdirs | `Auth/` + `Prescriptions/` subdirectories | Matches `docs/requeriments.md` §10; scales for Batch 6 `Users/`; already empty `Requests/` dir |
| Error handler | Handler class vs `withExceptions()` closure | `withExceptions()` closure | Existing `bootstrap/app.php` already uses it; Laravel 13 convention; no extra file |
| Patient search | LIKE on users.name vs full-text vs service | `Patient::with('user')->whereHas('user', fn)` with LIKE | Simplest for MVP; doctor selects patient by name; acceptable perf without index for now |
| Resource relationships | `whenLoaded()` vs always-include vs separate resources | `whenLoaded()` | Prevents N+1: `items` omitted in list views, included in `show` where `->load('items')` is called |
| ConsumePrescriptionRequest body | Empty rules vs no body validation | `rules()` returns `[]` | Only `authorize()` delegates to policy; no request body needed for PUT consume |
| Service exception format | Raw throw vs standardized wrapper | Keep current `HttpResponseException`; handler wraps it | Minimal change to `PrescriptionService`; handler normalizes the envelope |

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `app/Http/Requests/Auth/LoginRequest.php` | **Create** | `email` required\|email, `password` required\|string |
| `app/Http/Requests/Prescriptions/StorePrescriptionRequest.php` | **Create** | `patient_id` exists, `items.*` nested rules |
| `app/Http/Requests/Prescriptions/PrescriptionFilterRequest.php` | **Create** | `status` in:pending,consumed, `from`/`to` date with `after_or_equal` |
| `app/Http/Requests/Prescriptions/ConsumePrescriptionRequest.php` | **Create** | Empty `rules()`, `authorize()` uses `PrescriptionPolicy::consume` |
| `app/Http/Resources/UserResource.php` | **Create** | `{id, name, email, role}` via `getRoleNames()->first()` |
| `app/Http/Resources/PatientResource.php` | **Create** | `{id, birth_date, user: {id, name, email}}` via `whenLoaded` |
| `app/Http/Resources/PrescriptionResource.php` | **Create** | `{id, code, status, notes, consumed_at, created_at}` + doctor/patient/items via `whenLoaded` |
| `app/Http/Resources/PrescriptionItemResource.php` | **Create** | `{id, name, dosage, quantity, instructions}` |
| `app/Http/Controllers/PatientController.php` | **Create** | `index()` — search patients by name, paginated |
| `bootstrap/app.php` | **Modify** | Add `render()` callback mapping exceptions to `{message, code, details}` |
| `app/Http/Controllers/AuthController.php` | **Modify** | `login` → `LoginRequest` + `UserResource`; `profile` → `UserResource` |
| `app/Http/Controllers/PrescriptionController.php` | **Modify** | All 5 methods use Form Requests + Resources |
| `routes/api.php` | **Modify** | Add `GET /api/patients` under `auth:sanctum` + `role:admin\|doctor` |
| `tests/Feature/RbacAuthTest.php` | **Modify** | Update error-shape assertions from `{message, errors}` to `{message, code, details}` |
| `app/Services/PrescriptionService.php` | **Modify** | Update `HttpResponseException` to include `code: "CONFLICT"` in body |

## Implementation Details

### Error Handler (`bootstrap/app.php`)

```php
->withExceptions(function (Exceptions $exceptions): void {
    $exceptions->shouldRenderJsonWhen(fn ($r) => $r->is('api/*'));

    $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
        return response()->json([
            'message' => 'Unauthenticated',
            'code' => 'UNAUTHORIZED',
        ], 401);
    });

    $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, $request) {
        return response()->json([
            'message' => $e->getMessage() ?: 'Forbidden',
            'code' => 'FORBIDDEN',
        ], 403);
    });

    $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, $request) {
        return response()->json([
            'message' => 'Not Found',
            'code' => 'NOT_FOUND',
        ], 404);
    });

    $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
        return response()->json([
            'message' => 'Validation failed',
            'code' => 'VALIDATION_ERROR',
            'details' => ['errors' => $e->errors()],
        ], 422);
    });
})
```

The `HttpResponseException` from `PrescriptionService` passes through unchanged; its body already carries `message`/`code` after the service update. Unhandled exceptions fall to Laravel's default 500 JSON renderer.

### PatientController

```php
namespace App\Http\Controllers;

use App\Http\Resources\PatientResource;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Patient::with('user')
            ->when($request->filled('query'), fn ($q) =>
                $q->whereHas('user', fn ($uq) =>
                    $uq->where('name', 'like', '%'.$request->query('query').'%')
                )
            )
            ->orderByDesc('created_at')
            ->paginate(min((int) $request->input('limit', 15), 100));

        return PatientResource::collection($query)->response();
    }
}
```

### Route Addition (`routes/api.php`)

```php
Route::middleware(['auth:sanctum', 'role:admin|doctor'])->group(function () {
    Route::get('/patients', [PatientController::class, 'index']);
});
```

### PrescriptionService Update

The `consumePrescription` method throws `HttpResponseException`. Update the body to include `code`:

```php
// Before
throw new HttpResponseException(
    response()->json(['message' => 'Prescription is already consumed'], 409)
);

// After
throw new HttpResponseException(
    response()->json(['message' => 'Prescription is already consumed', 'code' => 'CONFLICT'], 409)
);
```

### Controller Refactors (Before/After patterns)

**AuthController::login** — inject `LoginRequest` instead of `Request`, return `UserResource`:

```php
// Before
public function login(Request $request): JsonResponse
{
    $request->validate([...]);
    ...
    return response()->json([..., 'user' => ['id' => ..., 'role' => $user->getRoleNames()->first()]], 201);
}

// After
public function login(LoginRequest $request): JsonResponse
{
    ...
    return response()->json([
        'access_token' => $token,
        'token_type' => 'Bearer',
        'user' => new UserResource($user),
    ], 201);
}
```

**PrescriptionController::store** — inject `StorePrescriptionRequest`, return `PrescriptionResource`:

```php
// Before: public function store(Request $request, PrescriptionService $svc)
// After:  public function store(StorePrescriptionRequest $request, PrescriptionService $svc)
//
// Before: return response()->json($prescription->toArray(), 201);
// After:  return (new PrescriptionResource($prescription))->response()->setStatusCode(201);
```

**PrescriptionController::index / myPrescriptions** — inject `PrescriptionFilterRequest`, return `PrescriptionResource::collection()`:

```php
// Before: return response()->json($prescriptions);
// After:  return PrescriptionResource::collection($prescriptions)->response();
```

**PrescriptionController::show** — return `PrescriptionResource` with items:

```php
// Before: return response()->json($prescription->load('items')->toArray());
// After:  return (new PrescriptionResource($prescription->load(['items', 'doctor.user', 'patient.user'])))->response();
```

**PrescriptionController::consume** — inject `ConsumePrescriptionRequest`:

```php
// Before: public function consume(Prescription $prescription, PrescriptionService $svc)
// After:  public function consume(ConsumePrescriptionRequest $request, Prescription $prescription, PrescriptionService $svc)
```

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Feature | Error responses use `{message, code, details}` | Update `RbacAuthTest` assertions; verify 422 returns `VALIDATION_ERROR` code, 401 returns `UNAUTHORIZED` |
| Feature | Patient search endpoint | New test: search by query returns filtered results, no query returns all, role enforcement |
| Feature | Resource shapes match contracts | Assert `UserResource`, `PatientResource`, `PrescriptionResource` keys on known endpoints |
| Feature | Form Request validation | Verify `StorePrescriptionRequest` rejects missing items, invalid patient_id |

Existing login test (`assertJsonStructure(['access_token', 'token_type', 'user'])`) remains valid — `user` key shape changes from inline array to `UserResource` but the structure is identical.

## Open Questions

None — all decisions resolved in exploration.
