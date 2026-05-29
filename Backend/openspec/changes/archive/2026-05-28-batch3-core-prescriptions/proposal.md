# Proposal: Core Prescriptions (Batch 3)

## Intent

Deliver the prescription lifecycle needed by doctors and patients: create prescriptions with items, list/filter/paginate doctor and patient views, inspect details with policy checks, and consume prescriptions. This also closes Batch 2 schema gaps so the API matches the documented domain model.

## Scope

### In Scope
- Add schema migration for `prescriptions.notes`, `prescriptions.consumed_at`, composite index `(status, created_at)`, `prescription_items.name`, and `prescription_items.quantity`.
- Update `Prescription` and `PrescriptionItem` model fillable fields and factories.
- Add `PrescriptionController` with 5 endpoints, `PrescriptionService` for transactional create/consume, and `PrescriptionPolicy` for view/create/consume authorization.
- Add `routes/api.php` entries for doctor and patient prescription flows.

### Out of Scope
- Form Requests and API Resources (Batch 4).
- PDF generation, admin metrics, and seeders (Batches 5-7).
- Automated tests (Batch 8).

## Capabilities

### New Capabilities
- `prescription-lifecycle`: doctor create/list/detail, patient list/consume, filtering, pagination, and ownership-based authorization.

### Modified Capabilities
- None.

## Approach

Use a thin controller with inline validation, a service for DB transactions, and a policy for resource ownership. Keep responses as raw JSON for now, matching the batch plan. Use implicit route model binding for prescription detail/consume routes.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `database/migrations/` | Modified | Fix prescription/item schema gaps and indexes |
| `app/Models/Prescription.php` | Modified | Add `notes` fillable/casts if needed |
| `app/Models/PrescriptionItem.php` | Modified | Rename fillable and add `quantity` |
| `database/factories/PrescriptionItemFactory.php` | Modified | Generate `name` and `quantity` |
| `app/Http/Controllers/PrescriptionController.php` | New | Batch 3 endpoints |
| `app/Services/PrescriptionService.php` | New | Transactional create/consume logic |
| `app/Policies/PrescriptionPolicy.php` | New | Ownership-based authorization |
| `routes/api.php` | Modified | Register prescription routes |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Column rename breaks factory or queries | Medium | Update model/factory references in the same change |
| Transactional create/consume logic is incomplete | Medium | Keep business logic in one service and wrap writes in `DB::transaction()` |
| Raw JSON shape changes later in Batch 4 | Low | Keep responses simple and stable for resource wrapping later |

## Rollback Plan

Revert the Batch 3 migration and remove controller/service/policy/route additions. If already migrated, run the rollback step for the schema migration, then restore the Batch 2 column names and indexes.

## Dependencies

- Batch 1 auth/RBAC and Batch 2 domain models/migrations must already be in place.

## Success Criteria

- [ ] The Batch 3 endpoints are defined and match the documented role/policy boundaries.
- [ ] The prescription schema matches the requirements for notes, consumption timestamp, and item naming/quantity.
- [ ] The proposal cleanly defers validation/resources/tests to later batches without blocking the lifecycle flow.
