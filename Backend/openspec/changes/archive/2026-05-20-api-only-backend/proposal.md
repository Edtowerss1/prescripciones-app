# Proposal: Convert Laravel App to API-Only REST Backend

## Intent

Turn the current Laravel skeleton into a stateless backend that serves JSON over `/api/*` only. This removes the web/Blade/Vite surface and establishes API auth now so the project does not need a second rework immediately after.

## Scope

### In Scope
- Replace `routes/web.php` with `routes/api.php` and API route registration in `bootstrap/app.php`.
- Remove the default UI stack: `resources/views/welcome.blade.php`, `resources/js/app.js`, `resources/css/app.css`, `vite.config.js`, and npm-based Composer scripts.
- Add token-based API auth foundation with Laravel Sanctum and JSON-first exception handling.
- Update the default feature test to cover an API endpoint.

### Out of Scope
- Business/domain endpoints and CRUD resources.
- Frontend app creation or SPA/mobile client work.
- OpenAPI/Swagger documentation.

## Capabilities

### New Capabilities
- `api-foundation`: stateless API routing, JSON responses, and removal of web-only rendering.
- `api-authentication`: Sanctum-based token authentication for API consumers.

### Modified Capabilities
- None.

## Approach

Follow the recommended “full” path from exploration: remove the web layer, introduce API routing, and add Sanctum in the same change. Keep Laravel conventions, force JSON for API errors, and keep the change minimal because this is still a fresh skeleton.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `bootstrap/app.php` | Modified | Register API routing and JSON exception behavior |
| `routes/web.php` | Removed | No web routes in API-only mode |
| `routes/api.php` | New | API entry routes and future resource groups |
| `config/auth.php` | Modified | Add API guard/token auth configuration |
| `resources/views/*`, `resources/js/*`, `resources/css/*`, `vite.config.js` | Removed | Eliminate UI assets |
| `composer.json`, `package.json` | Modified/Removed | Remove frontend build flow and npm coupling |
| `tests/Feature/ExampleTest.php` | Modified | Validate API behavior instead of `/` web response |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Breaking the default test and route expectations | High | Update tests and introduce a known API endpoint before removing web routes |
| JSON error formatting still returning HTML | Medium | Force JSON responses for API requests in exception handling |
| Auth configuration drift during Sanctum setup | Medium | Keep auth changes small and verify config against Laravel docs |

## Rollback Plan

Restore `routes/web.php`, the welcome view, frontend assets, Vite config, and original Composer scripts. Remove Sanctum/auth changes and revert `bootstrap/app.php` to the web route stack.

## Dependencies

- Laravel Sanctum.
- Any CI/CD step that still assumes npm-based asset compilation.

## Success Criteria

- [ ] The application serves API routes without any web view dependency.
- [ ] Default tests pass against API behavior.
- [ ] The repo no longer requires Vite/npm for normal backend execution.
