# Exploration: Batch 5 — PDF Generation for Prescriptions

## Executive Summary

Batch 5 adds a `GET /api/prescriptions/{id}/pdf` endpoint that generates and returns a PDF of a prescription. The implementation requires installing `barryvdh/laravel-dompdf`, creating a `PdfService` for generation logic, a Blade view for the PDF template, and a new controller method gated by the existing `PrescriptionPolicy::view`. All required data is already available through existing model relationships.

## Current State

### Models & Relationships (already in place)
- **Prescription** — `belongsTo Doctor`, `belongsTo Patient`, `hasMany PrescriptionItem`, auto-generates UUID `code`
- **Doctor** — `belongsTo User`, `hasMany Prescription`, has `specialty` field
- **Patient** — `belongsTo User`, `hasMany Prescription`, has `birth_date` field
- **PrescriptionItem** — `belongsTo Prescription`, has `name`, `dosage`, `quantity`, `instructions`

### Authorization (already in place)
- **PrescriptionPolicy::view** — admin OR doctor-owner OR patient-owner
- Controller uses `Gate::allows('view', $prescription)` pattern in `show()` method

### Existing patterns
- Controller methods return `JsonResponse` with `PrescriptionResource`
- Service layer (`PrescriptionService`) handles business logic
- Routes use `auth:sanctum` middleware + policy gates for detail endpoints
- Tests use Pest 4 with `RefreshDatabase`

### What's missing
- `barryvdh/laravel-dompdf` is **NOT** in `composer.json`
- No `PdfService` exists (suggested in `docs/requeriments.md` §10 structure)
- No Blade views exist at all (this is an API-only app so far)
- No PDF-related routes

## Affected Areas

| File | Change |
|------|--------|
| `composer.json` | Add `barryvdh/laravel-dompdf` dependency |
| `routes/api.php` | Add `GET /api/prescriptions/{prescription}/pdf` route |
| `app/Http/Controllers/PrescriptionController.php` | Add `pdf()` method |
| `app/Services/PdfService.php` | **NEW** — PDF generation service |
| `resources/views/pdf/prescription.blade.php` | **NEW** — PDF template |
| `tests/Feature/PrescriptionPdfTest.php` | **NEW** — PDF endpoint tests |

## Architecture Decisions

### 1. PDF Generation: Service vs Controller

**Decision: `PdfService` class**

```
app/Services/PdfService.php
  └── generatePrescription(Prescription $prescription): \Illuminate\Http\Response
```

**Rationale:** The requirements doc (§10) already lists `PdfService` in the suggested structure. PDF generation involves loading data, rendering a view, and returning a response — this is business logic, not controller responsibility. The controller should only handle authorization and delegate to the service. This follows the same pattern as `PrescriptionService`.

### 2. PDF Template: Blade View vs Raw HTML

**Decision: Blade view at `resources/views/pdf/prescription.blade.php`**

**Rationale:**
- Blade is the standard Laravel templating engine — no reason to fight it
- Easier to maintain, read, and style than raw HTML strings in PHP
- Dompdf works natively with Blade-rendered HTML via `PDF::loadView()`
- Separation of concerns: template in `.blade.php`, logic in service
- The requirements say "Generación de PDF desde backend" with Dompdf — Blade is the idiomatic pairing

### 3. Response Type: Force Download vs Inline

**Decision: Force download (`Content-Disposition: attachment`)**

**Rationale:**
- The requirements say "Descarga el PDF" (download the PDF) — user intent is to save the file
- The endpoint is `GET /api/prescriptions/{id}/pdf` — an API consumer (Vue frontend) will likely trigger a browser download or blob save
- Force download is safer: guarantees the file is saved rather than rendered in a browser tab where it might be lost
- Filename: `prescription-{code}.pdf` — uses the unique prescription code for traceability

### 4. Authorization Pattern

**Decision: Reuse `Gate::allows('view', $prescription)` — same as `show()`**

```php
public function pdf(Prescription $prescription, PdfService $pdfService): Response
{
    if (! Gate::allows('view', $prescription)) {
        abort(404);
    }

    return $pdfService->generatePrescription($prescription);
}
```

**Rationale:** The requirements (§5 API contract) specify the same access rules for `/pdf` as for `/{id}` — doctor owner, patient owner, admin. The `PrescriptionPolicy::view` method already implements this logic exactly. No new policy method needed. Using `abort(404)` matches the existing `show()` pattern (not 403 — we don't leak existence).

### 5. Route Placement

**Decision: Same `auth:sanctum` group as `show` and `consume`**

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/prescriptions/{prescription}', [PrescriptionController::class, 'show']);
    Route::put('/prescriptions/{prescription}/consume', [PrescriptionController::class, 'consume']);
    Route::get('/prescriptions/{prescription}/pdf', [PrescriptionController::class, 'pdf']);
});
```

**Rationale:** The PDF endpoint has the same authorization as `show` (policy-gated, any authenticated user). It should live in the same middleware group for consistency.

### 6. PDF Data Loading

**Decision: Eager-load all relationships in the service**

```php
$prescription->load(['items', 'doctor.user', 'patient.user']);
```

**Rationale:** The PDF needs data from all relationships. Eager loading prevents N+1 queries. This is the same pattern used in `PrescriptionController::show()`.

## PDF Content (from §12 requirements)

| Field | Source |
|-------|--------|
| Código de prescripción | `$prescription->code` |
| Fecha de creación | `$prescription->created_at` |
| Estado | `$prescription->status` |
| Datos del paciente | `$prescription->patient->user->name` |
| Datos del médico | `$prescription->doctor->user->name`, `$prescription->doctor->specialty` |
| Notas | `$prescription->notes` |
| Lista de ítems | `$prescription->items` → name, dosage, quantity, instructions |

### Optional Plus items (§12)
- QR with prescription code — deferred to future batch
- Doctor signature — deferred to future batch
- Institutional design — deferred to future batch

## Approaches Comparison

### PDF Library

| Approach | Pros | Cons | Complexity |
|----------|------|------|------------|
| `barryvdh/laravel-dompdf` (recommended) | Requirements-recommended, Laravel-native, Blade integration, well-documented | Limited CSS support, not ideal for complex layouts | Low |
| `spatie/browsershot` (Puppeteer) | Full Chrome rendering, perfect CSS/JS support | Requires Node.js + Puppeteer, heavier dependency | High |
| `mpdf/mpdf` | Better CSS support than Dompdf | No native Blade integration, more manual setup | Medium |

### Controller Method vs Separate Controller

| Approach | Pros | Cons | Complexity |
|----------|------|------|------------|
| Method in `PrescriptionController` (recommended) | Same resource, same authorization, consistent with `show`/`consume` pattern | Controller grows slightly | Low |
| Separate `PrescriptionPdfController` | Single responsibility | Duplicates authorization logic, extra route registration | Medium |

## Risks

1. **Dompdf CSS limitations** — Dompdf has limited CSS3 support (no flexbox, limited grid). The PDF template must use simple CSS (tables, floats, inline styles). Mitigation: Use a table-based layout for the prescription items list.

2. **Font rendering** — Dompdf may have issues with special characters or non-Latin fonts. Mitigation: Use standard fonts or embed a font file if needed. The app uses Spanish text which should work fine with default fonts.

3. **Large prescription items** — If a prescription has many items, the PDF could span multiple pages. Mitigation: Dompdf handles page breaks automatically. Add `page-break-inside: avoid` for item rows.

4. **Package installation** — Adding `barryvdh/laravel-dompdf` requires `composer install` and may need `php artisan vendor:publish` for config. Mitigation: This is a standard Laravel package installation process.

5. **Testing PDF output** — Testing binary PDF content is tricky. Mitigation: Test the endpoint returns a 200 with `application/pdf` content type and a non-empty body. Don't assert PDF content structure — that's a Dompdf concern. For deeper testing, use the service directly and assert the HTML content before PDF conversion.

## Scope Summary

| Deliverable | Files | Effort |
|-------------|-------|--------|
| Install `barryvdh/laravel-dompdf` | `composer.json` + publish config | Low |
| `PdfService` | 1 new file | Low |
| PDF Blade template | 1 new file | Low |
| `pdf()` method in controller | Modify existing file | Low |
| Route addition | Modify `routes/api.php` | Low |
| Tests | 1 new test file | Medium |
| **Total** | **3 new files, 2 modified** | **Low-Medium** |

## Ready for Proposal

**Yes.** The exploration is complete. All architecture decisions have clear rationale, the scope is well-bounded (3 new files, 2 modified), and the approach is consistent with existing Laravel conventions and the project's patterns. The orchestrator should proceed to `sdd-propose`.
