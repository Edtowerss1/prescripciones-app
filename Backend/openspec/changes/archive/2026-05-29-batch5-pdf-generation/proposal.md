# Proposal: Prescription PDF Generation

## Intent

Add a secure PDF download for prescriptions so authenticated users can export the same prescription data already visible in the API. This supports the Batch 5 requirement without changing authorization rules or introducing admin-only behavior.

## Scope

### In Scope
- Install `barryvdh/laravel-dompdf`
- Add `PdfService::generatePrescriptionPdf(Prescription): BinaryFileResponse`
- Create `resources/views/pdf/prescription.blade.php`
- Add `GET /api/prescriptions/{id}/pdf` under `auth:sanctum`
- Reuse `Gate::allows('view', $prescription)` + `abort(404)` and force download as `prescription-{code}.pdf`

### Out of Scope
- QR code, doctor signature, institutional branding
- Admin-only PDF endpoints or new permission rules

## Capabilities

### New Capabilities
- `prescription-pdf-generation`: generate and download a prescription PDF from the authenticated API

### Modified Capabilities
- None

## Approach

- Keep authorization identical to `show()` to avoid leaking prescription existence.
- Move PDF rendering into `PdfService`; keep the controller thin.
- Render a Blade template with prescription, doctor, patient, notes, and items.
- Return a forced download response named `prescription-{code}.pdf`.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `composer.json` | Modified | Add Dompdf dependency |
| `app/Http/Controllers/PrescriptionController.php` | Modified | Add `pdf()` endpoint method |
| `app/Services/PdfService.php` | New | PDF generation service |
| `resources/views/pdf/prescription.blade.php` | New | PDF template |
| `routes/api.php` | Modified | Register `/prescriptions/{id}/pdf` |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Dompdf CSS limitations | Medium | Use simple table-based layout |
| Large prescriptions span multiple pages | Medium | Keep template page-break friendly |
| Binary response behavior differs across clients | Low | Force download and verify API headers in tests |

## Rollback Plan

Remove the route, controller method, service, and view; then uninstall the package and revert the composer lockfile update.

## Dependencies

- `barryvdh/laravel-dompdf`

## Success Criteria

- [ ] Authenticated users can download a PDF for prescriptions they may view.
- [ ] The response downloads as `prescription-{code}.pdf` with PDF content type.
- [ ] Unauthorized access still returns `404` via the existing policy check.
