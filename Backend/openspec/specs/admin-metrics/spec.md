# Spec: Admin Metrics Dashboard (delta to api-foundation)

## Capability

`admin-metrics`

## Requirements

### 1. Totals
The system MUST return `totals.doctors`, `totals.patients`, and `totals.prescriptions` as non-negative integers.

### 2. By Status
The system MUST return `by_status.pending` and `by_status.consumed` as non-negative integers.

### 3. By Day
The system MUST return `by_day` as an array of `{date, count}` objects ordered by ascending date.

### 4. Top Doctors
The system MUST return `top_doctors` as an array of `{doctor_id, doctor_name, count}` objects ordered by descending prescription count.

## Scenarios

### Scenario 1: Admin gets metrics
**Given** an authenticated admin with prescriptions in the system
**When** they request `GET /api/admin/metrics`
**Then** the response MUST include all four metric sections
**And** the response MUST match the documented JSON shape.

### Scenario 2: Empty data returns safe zeros
**Given** an authenticated admin and no doctors, patients, or prescriptions
**When** they request `GET /api/admin/metrics`
**Then** totals and status counts MUST be `0`
**And** `by_day` and `top_doctors` MUST be empty arrays.

### Scenario 3: Date range filters prescription metrics
**Given** an authenticated admin and prescriptions inside and outside a date range
**When** they request `GET /api/admin/metrics?from=2026-05-01&to=2026-05-31`
**Then** `by_status`, `by_day`, and `top_doctors` MUST reflect only prescriptions inside the range
**And** `totals.doctors` and `totals.patients` MUST remain global counts.

### Scenario 4: Non-admin is blocked
**Given** an authenticated doctor or patient
**When** they request `GET /api/admin/metrics`
**Then** the request MUST be denied with an authorization error.

### Scenario 5: Unauthenticated request is blocked
**Given** no authenticated user
**When** they request `GET /api/admin/metrics`
**Then** the request MUST be rejected with an authentication error.

### Scenario 6: Invalid range is rejected
**Given** a request where `from` or `to` is malformed or `from` is after `to`
**When** the endpoint validates the input
**Then** the request MUST fail with a validation error.
