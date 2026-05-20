# api-foundation Specification

## Purpose

Defines the stateless API-only architecture: routing, response format, and removal of web/Blade/Vite surface.

## Requirements

### Requirement: API-Only Routing

The application MUST register routes exclusively through `routes/api.php` prefixed with `/api`. The `routes/web.php` file MUST be removed.

#### Scenario: API root returns JSON

- GIVEN the application is running
- WHEN a GET request is sent to `/api`
- THEN the response status SHALL be 200
- AND the `Content-Type` header SHALL be `application/json`

#### Scenario: Former web routes return 404

- GIVEN the web route file has been removed
- WHEN a GET request is sent to `/`
- THEN the response status SHALL be 404

#### Scenario: Health endpoint still works

- GIVEN the `/up` health check is registered in `bootstrap/app.php`
- WHEN a GET request is sent to `/up`
- THEN the response status SHALL be 200

### Requirement: JSON-First Exception Handling

All error responses for requests under the `/api` prefix MUST be rendered as JSON, never HTML.

#### Scenario: 404 errors return JSON

- GIVEN an API route was requested
- WHEN the resource is not found
- THEN the response SHALL have `Content-Type: application/json`
- AND the body SHALL contain a JSON object with at least a `message` key

#### Scenario: Validation errors return JSON

- GIVEN an API route that requires validation
- WHEN invalid input is submitted
- THEN the response SHALL be JSON with status 422
- AND the body SHALL contain an `errors` object

### Requirement: No UI Asset Dependency

The application MUST NOT require Vite, Blade views, CSS, JS asset compilation, or npm for normal backend execution.

#### Scenario: Application boots without npm

- GIVEN `node_modules` is absent
- WHEN `php artisan serve` is invoked
- THEN the application SHALL boot and serve API responses without errors

### Requirement: No Frontend Build Scripts

Composer scripts that invoke npm (`setup` and `dev`) MUST be removed or rewritten to exclude frontend tooling.

#### Scenario: `composer run test` works without npm

- GIVEN the `test` Composer script does not depend on npm
- WHEN `composer run test` is executed
- THEN tests SHALL run and pass without invoking npm or Vite
