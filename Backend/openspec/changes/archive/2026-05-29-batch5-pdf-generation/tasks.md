# Tasks: Batch 5 — Prescription PDF Generation

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~150 |
| 400-line budget risk | Low |
| Chained PRs recommended | No |
| Suggested split | Single PR |
| Delivery strategy | ask-always |
| Chain strategy | size-exception |

Decision needed before apply: Yes
Chained PRs recommended: No
Chain strategy: size-exception
400-line budget risk: Low

### Suggested Work Units

| Unit | Goal | Likely PR | Notes |
|------|------|-----------|-------|
| 1 | Full PDF generation feature | PR 1 | Single PR, ~150 lines, within budget |

## Phase 1: Install Dependency + Create Directory

- [ ] 1.1 Run `composer require barryvdh/laravel-dompdf` to install Dompdf
- [ ] 1.2 Create `resources/views/pdf/` directory for PDF templates

## Phase 2: Blade Template

- [ ] 2.1 Create `resources/views/pdf/prescription.blade.php` with Dompdf-compatible table layout: code, date, status, patient, doctor, specialty, notes, and items table

## Phase 3: PdfService

- [ ] 3.1 Create `app/Services/PdfService.php` with `generatePrescriptionPdf(Prescription $prescription): Response` using `Pdf::loadView()->download()`

## Phase 4: Controller Method + Route

- [ ] 4.1 Add `pdf(Prescription $prescription, PdfService $pdfService): Response` to `PrescriptionController` with `Gate::allows('view', $prescription)` → `abort(404)` and eager-loading
- [ ] 4.2 Add `GET /prescriptions/{prescription}/pdf` route in `routes/api.php` under the existing `auth:sanctum` group

## Phase 5: Tests

- [ ] 5.1 Create `tests/Feature/PrescriptionPdfTest.php` covering: owner downloads 200 + PDF content-type + attachment disposition, non-owner 404, nonexistent 404
