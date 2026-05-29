# Design: Admin Metrics Dashboard

## Summary

Implement a dedicated admin metrics pipeline using controller, service, and resource layers.

## Architecture

- `AdminMetricController` handles validation and access to the service.
- `AdminMetricService` computes totals and prescription aggregates.
- `AdminMetricResource` maps the response to the dashboard contract.

## Decisions

- Use read-only queries only.
- Apply `from`/`to` filters to prescription metrics only.
- Preserve global counts for doctors and patients.
