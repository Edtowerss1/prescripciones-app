# Delta for api-foundation

## ADDED Requirements

### Requirement: Standardized Error Envelope

All API error responses under `/api` MUST use the envelope `{message: string, code: string, details?: object}`. The `code` field SHALL be a snake_case constant mapped from the HTTP status. The `details` object, when present, SHALL contain error-specific payload (e.g., validation errors).

#### Scenario: 422 returns standardized validation errors

- GIVEN a request fails validation
- WHEN the response is 422
- THEN body SHALL be `{message, code: "VALIDATION_ERROR", details: {errors: {...}}}`

#### Scenario: 401 returns standardized unauthorized

- GIVEN an unauthenticated request to a protected route
- WHEN the response is 401
- THEN body SHALL be `{message: "Unauthenticated", code: "UNAUTHORIZED"}`

### Requirement: Error Code Mapping

The exception handler in `bootstrap/app.php` MUST map HTTP status codes to error codes as follows: 400→BAD_REQUEST, 401→UNAUTHORIZED, 403→FORBIDDEN, 404→NOT_FOUND, 409→CONFLICT, 422→VALIDATION_ERROR, 500→SERVER_ERROR.

#### Scenario: 409 produces CONFLICT code

- GIVEN a business rule conflict (e.g., duplicate consumption)
- WHEN the response is 409
- THEN body SHALL contain `code: "CONFLICT"`

#### Scenario: 404 from ModelNotFoundException produces NOT_FOUND code

- GIVEN a route uses implicit model binding for a non-existent resource
- WHEN the response is 404
- THEN body SHALL contain `code: "NOT_FOUND"`

## MODIFIED Requirements

### Requirement: JSON-First Exception Handling

All error responses for requests under the `/api` prefix MUST be rendered as JSON using the standardized `{message, code, details}` envelope, never HTML.
(Previously: JSON responses required but envelope format was unspecified.)

#### Scenario: 404 errors return JSON

- GIVEN an API route was requested
- WHEN the resource is not found
- THEN the response SHALL have `Content-Type: application/json`
- AND the body SHALL contain `message`, `code`, and `details` keys

#### Scenario: Validation errors return JSON

- GIVEN an API route that requires validation
- WHEN invalid input is submitted
- THEN the response SHALL be JSON with status 422
- AND the body SHALL be `{message, code: "VALIDATION_ERROR", details: {errors: {...}}}`
