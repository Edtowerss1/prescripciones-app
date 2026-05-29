# Backend delivery batches (traceability)

This document records the implementation batches for the backend requirements in `docs/requeriments.md`. It is meant to provide a clear, reviewable plan with ordering and scope.

## Quick path

1. Implement Batch 1 (Auth + base RBAC)
2. Implement Batch 2 (Models + migrations)
3. Implement Batch 3 (Core prescriptions)
4. Implement Batch 4 (Requests + Resources + error format)
5. Implement Batch 5 (PDF)
6. Implement Batch 6 (Admin)
7. Implement Batch 7 (Seeders)
8. Implement Batch 8 (Tests)

## Batch details

| Batch | Scope | Purpose | Requirements coverage |
| --- | --- | --- | --- |
| 1 | Auth + base RBAC | Enable login/logout/profile and basic role protection | Auth endpoints, role protection, Sanctum tokens |
| 2 | Models + migrations | Establish schema, relations, indexes | Users, doctors, patients, prescriptions, items, suggested indexes |
| 3 | Core prescriptions | Doctor create, list, detail; patient consume; filters/pagination; policies | Prescriptions CRUD-lite, access control rules |
| 4 | Requests + Resources + errors | Validation and response consistency | Form Requests, API Resources, standard error format |
| 5 | PDF | Generate prescription PDF | `/api/prescriptions/{id}/pdf`, Dompdf |
| 6 | Admin | Metrics + optional global prescriptions list | Admin metrics endpoint, optional list |
| 7 | Seeders | Test users + sample data | Required demo accounts + sample prescriptions |
| 8 | Tests | Minimum backend tests | Login, create prescription, role restriction, consume prescription |

## Notes

- This plan is ordered to unlock dependencies early (Auth, RBAC, schema) and keep user flows testable as soon as possible.
- Optional items (admin user creation, global prescriptions list) are placed after the core flow.

## Checklist

- [x] Batch 1 completed (archived as `2026-05-20-api-only-backend`)
- [ ] Batch 2 completed
- [x] Batch 3 completed (archived as `2026-05-28-batch3-core-prescriptions`)
- [ ] Batch 4 completed
- [ ] Batch 5 completed
- [ ] Batch 6 completed
- [ ] Batch 7 completed
- [ ] Batch 8 completed
