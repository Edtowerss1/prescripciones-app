# Proposal: Admin Metrics Dashboard

## Intent

Add an admin-only metrics endpoint to expose totals, prescription status breakdowns, daily volume, and top doctors for the dashboard.

## Scope

- New `GET /api/admin/metrics` endpoint
- Admin-only access via existing auth/RBAC
- JSON resource contract for dashboard consumption
- Test coverage for access control, validation, and metric aggregation

## Notes

- Metrics are read-only and derived from existing models.
- Date filters should narrow prescription-based metrics without changing global user totals.
