# Exploration: Admin Metrics Dashboard

## Findings

- The dashboard can be served from a dedicated controller/service/resource pipeline.
- Existing auth and role middleware are sufficient for admin-only access.
- Prescription-based aggregates should accept optional `from`/`to` filters.

## Considerations

- Keep totals for doctors and patients global.
- Keep prescription metrics deterministic for empty datasets.
- Ensure top-doctor ordering is stable and limited to the top five results.
