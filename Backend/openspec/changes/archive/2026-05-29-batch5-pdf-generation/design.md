# Design: Prescription PDF Generation

## Technical Approach

Add a `GET /api/prescriptions/{prescription}/pdf` endpoint under `auth:sanctum` that renders a Blade view to PDF via Dompdf and returns a forced download. The controller reuses `Gate::allows('view', $prescription)` + `abort(404)` — identical to `show()`. A new `PdfService` encapsulates Dompdf usage; the controller stays thin (authorization only). Eager-loading happens in the controller, matching the `show()` pattern.

## Architecture Decisions

| Option | Tradeoff | Decision |
|--------|----------|----------|
| Dompdf (reqs-recommended) vs Browsershot vs mPDF | Dompdf: limited CSS, zero infra. Browsershot: full CSS, needs Node. mPDF: better CSS, no Blade-native API | **Dompdf** — reqs-recommended, meets current needs, simplest install |
| Separate Controller vs method on existing | Separate: SR but duplicates auth. Method: one file grows, zero duplication | **Method on PrescriptionController** — same resource, same policy |
| `Pdf` facade vs DI `Barryvdh\DomPDF\PDF` | Facade: simple. DI: testable | **Facade** — service already wraps it, fakes with `Pdf::shouldReceive()` in tests |
| Eager-load in controller vs service | Controller: matches `show()` pattern. Service: hides from controller | **Controller** — consistency with existing pattern |
| Return `Response` vs `BinaryFileResponse` | `download()` returns `\Illuminate\Http\Response` (wrapping `BinaryFileResponse`) | **`Response`** — matches actual return type of `Pdf::loadView()->download()` |

## Data Flow

```
GET /api/prescriptions/{prescription}/pdf
    │
    ▼
PrescriptionController::pdf()
    │ Gate::allows('view', $prescription) → abort(404)
    │ $prescription->load(['items', 'doctor.user', 'patient.user'])
    ▼
PdfService::generatePrescriptionPdf($prescription)
    │ Pdf::loadView('pdf.prescription', ['prescription' => $prescription])
    │     → download("prescription-{$prescription->code}.pdf")
    ▼
\Illuminate\Http\Response
    Content-Type: application/pdf
    Content-Disposition: attachment; filename="prescription-{code}.pdf"
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `composer.json` | Modify | Add `barryvdh/laravel-dompdf` to require |
| `app/Services/PdfService.php` | Create | `generatePrescriptionPdf(Prescription): Response` — renders Blade view, returns forced download |
| `resources/views/pdf/prescription.blade.php` | Create | Table-based layout: code, date, status, patient, doctor, notes, items table |
| `app/Http/Controllers/PrescriptionController.php` | Modify | Add `pdf(Prescription, PdfService): Response` method with gate check |
| `routes/api.php` | Modify | Add `GET /prescriptions/{prescription}/pdf` route in existing `auth:sanctum` group |
| `tests/Feature/PrescriptionPdfTest.php` | Create | Feature test: owner download, non-owner 404, nonexistent 404, content-type and disposition assertions |

## Interfaces / Contracts

### PdfService

```php
namespace App\Services;

use App\Models\Prescription;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class PdfService
{
    public function generatePrescriptionPdf(Prescription $prescription): Response
    {
        return Pdf::loadView('pdf.prescription', [
            'prescription' => $prescription,
        ])->download("prescription-{$prescription->code}.pdf");
    }
}
```

### PrescriptionController::pdf

```php
public function pdf(Prescription $prescription, PdfService $pdfService): Response
{
    if (! Gate::allows('view', $prescription)) {
        abort(404);
    }

    $prescription->load(['items', 'doctor.user', 'patient.user']);

    return $pdfService->generatePrescriptionPdf($prescription);
}
```

### Route

```php
// Inside existing auth:sanctum group (lines 47-50)
Route::get('/prescriptions/{prescription}/pdf', [PrescriptionController::class, 'pdf']);
```

### Blade View (`resources/views/pdf/prescription.blade.php`)

Table-based layout because Dompdf has limited CSS support. Spanish labels matching the domain. Conditional notes section. `page-break-inside: avoid` on item rows for multi-page prescriptions.

```blade
<!DOCTYPE html>
<html><head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Prescripción {{ $prescription->code }}</title>
<style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
  h1 { text-align: center; font-size: 18px; margin-bottom: 20px; }
  .section { margin-bottom: 18px; }
  .label { font-weight: bold; width: 120px; }
  table { width: 100%; border-collapse: collapse; }
  th, td { border: 1px solid #333; padding: 6px 8px; text-align: left; }
  th { background-color: #eee; }
  tr { page-break-inside: avoid; }
</style>
</head><body>

<h1>Prescripción Médica</h1>

<table class="section">
  <tr><td class="label">Código</td><td>{{ $prescription->code }}</td></tr>
  <tr><td class="label">Fecha</td><td>{{ $prescription->created_at->format('d/m/Y H:i') }}</td></tr>
  <tr><td class="label">Estado</td><td>{{ $prescription->status }}</td></tr>
</table>

<table class="section">
  <tr><td class="label">Paciente</td><td>{{ $prescription->patient->user->name }}</td></tr>
  <tr><td class="label">Médico</td><td>{{ $prescription->doctor->user->name }}</td></tr>
  <tr><td class="label">Especialidad</td><td>{{ $prescription->doctor->specialty }}</td></tr>
</table>

@if($prescription->notes)
<table class="section">
  <tr><td class="label">Notas</td><td>{{ $prescription->notes }}</td></tr>
</table>
@endif

<h2>Ítems</h2>
<table>
  <thead>
    <tr><th>Medicamento</th><th>Dosis</th><th>Cantidad</th><th>Instrucciones</th></tr>
  </thead>
  <tbody>
    @foreach($prescription->items as $item)
    <tr>
      <td>{{ $item->name }}</td>
      <td>{{ $item->dosage ?? '—' }}</td>
      <td>{{ $item->quantity }}</td>
      <td>{{ $item->instructions ?? '—' }}</td>
    </tr>
    @endforeach
  </tbody>
</table>

</body></html>
```

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Feature | Owner (doctor/patient) gets 200 + `application/pdf` + `attachment` header | Pest HTTP test with `assertHeader`, `assertSuccessful` |
| Feature | Non-owner gets 404 with standard error body | Pest HTTP test — same pattern as existing detail tests |
| Feature | Nonexistent prescription returns 404 (implicit binding) | Pest HTTP test for missing ID |
| Unit | `PdfService::generatePrescriptionPdf()` renders view, returns Response | Mock `Pdf` facade, assert `loadView()` called with correct view + data |

## Migration / Rollout

No data migration required. Rollback: remove route, controller method, service file, and view. Run `composer remove barryvdh/laravel-dompdf` and revert `composer.lock`.

## Open Questions

None — all decisions resolved.
