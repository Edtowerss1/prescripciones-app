# Proposal: Models + Migrations (Batch 2)

## Intent

Establish the domain schema with Eloquent models and migrations for doctors, patients, prescriptions, and prescription items. This unlocks all subsequent CRUD and workflow batches by having the data layer in place first.

## Scope

### In Scope
- `Doctor` model + migration: `user_id` FK, `specialty`, `license_number`
- `Patient` model + migration: `user_id` FK, `birth_date`
- `Prescription` model + migration: UUID `code`, `doctor_id` FK, `patient_id` FK, `status`, timestamps
- `PrescriptionItem` model + migration: `prescription_id` FK, `medication_name`, `dosage`, `instructions`
- `User` model relationships: `doctor()` and `patient()`
- All Eloquent relationships with return type hints
- Foreign keys with `constrained()`, indexes on FK and status columns
- Factories for each new model

### Out of Scope
- CRUD endpoints, API Resources, Form Requests (Batch 3/4)
- Policies/authorization (Batch 3)
- Soft deletes on prescriptions or items
- Prescription status state machine logic
- Seeders with sample data (Batch 7)

## Capabilities

### New Capabilities
- `doctor-model`: Doctor profile (`doctors` table) via one-to-one with `User`; `hasMany` prescriptions.
- `patient-model`: Patient profile (`patients` table) via one-to-one with `User`; `hasMany` prescriptions.
- `prescription-model`: Prescriptions with UUID `code` (public reference), `belongsTo` doctor and patient, `hasMany` items, `status` enum column.

### Modified Capabilities
- None.

## Approach

Generate migrations via `php artisan make:migration` and models via `php artisan make:model --factory`. Use `constrained()` for all foreign keys with `cascadeOnDelete()` for `user_id` (roles) and `restrictOnDelete()` for doctor/patient/prescription FKs. Prescription `code` uses `Str::uuid()` generated in `static::creating()`. Indexes on `status`, `doctor_id`, `patient_id`, and `prescription_id`. Follow existing conventions: PHP attributes over docblock annotations, `casts()` method for type conversion.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `app/Models/User.php` | Modified | Add `doctor()` and `patient()` relationships |
| `app/Models/Doctor.php` | New | `belongsTo` User, `hasMany` Prescription |
| `app/Models/Patient.php` | New | `belongsTo` User, `hasMany` Prescription |
| `app/Models/Prescription.php` | New | UUID `code`, `belongsTo` Doctor/Patient, `hasMany` PrescriptionItem |
| `app/Models/PrescriptionItem.php` | New | `belongsTo` Prescription |
| `database/migrations/` | New | 4 migrations (doctors, patients, prescriptions, prescription_items) |
| `database/factories/` | New | 4 factories |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| UUID generation edge case (collision) | Low | `Str::uuid()` produces v4 UUID; collision probability negligible |
| Foreign key cascade causing unexpected data loss | Low | Use `restrictOnDelete()` on domain FKs; only `cascadeOnDelete()` where roles are deleted with User |
| Migration order dependency | Low | Name migrations with sequential timestamps respecting dependency order |

## Rollback Plan

Run `php artisan migrate:rollback --step=4` to undo all 4 migrations. Factory and model files can be deleted manually. Remove `doctor()` and `patient()` from `User` model if rollback to Batch 1 state.

## Dependencies

- Batch 1 (auth + RBAC) completed and verified.
- `spatie/laravel-permission` installed for role-based user categorization.

## Success Criteria

- [ ] `php artisan migrate:fresh` creates all 4 tables with correct schema
- [ ] `php artisan test --compact` passes (existing tests + auto-generated model tests)
- [ ] `Prescription::factory()->create()` generates a valid UUID code
- [ ] `$user->doctor` and `$user->patient` relationships resolve correctly
