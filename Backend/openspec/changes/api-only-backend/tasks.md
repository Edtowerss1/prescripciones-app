# Tasks: Convert Laravel App to API-Only REST Backend

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~150 |
| 400-line budget risk | Low |
| Chained PRs recommended | No |
| Suggested split | Single PR |
| Delivery strategy | ask-on-risk |
| Chain strategy | pending |

Decision needed before apply: Yes
Chained PRs recommended: No
Chain strategy: pending
400-line budget risk: Low

## Phase 1: Foundation — Sanctum & API Routing

- [x] 1.1 Run `composer require laravel/sanctum` to install Sanctum dependency
- [x] 1.2 Run `php artisan install:api --no-interaction` to scaffold `routes/api.php`, Sanctum config, and migration
- [x] 1.3 Update `bootstrap/app.php`: replace `web:` with `api:` in `withRouting()`, add `shouldRenderJsonWhen(fn ($r) => $r->is('api/*'))` in `withExceptions()`
- [x] 1.4 Update `config/auth.php`: add `api` guard with `driver => 'sanctum'` and `provider => 'users'`
- [x] 1.5 Add `use Laravel\Sanctum\HasApiTokens;` to `App\Models\User`
- [x] 1.6 Create `routes/api.php` with: `GET /api` (health JSON), `POST /api/tokens` (issue token), `GET /api/user` (auth-protected), `DELETE /api/tokens/{id}` (revoke)

## Phase 2: Cleanup — Remove Web & Frontend Surface

- [x] 2.1 Delete `routes/web.php`
- [x] 2.2 Delete `resources/views/welcome.blade.php`, `resources/js/app.js`, `resources/css/app.css`
- [x] 2.3 Delete `vite.config.js`, `package.json`, `.npmrc`
- [x] 2.4 Update `composer.json`: remove `npm install --ignore-scripts`, `npm run build` from `setup` script; remove `npm run dev` from `dev` script

## Phase 3: Testing — Write Tests First (TDD: strict_tdd = true)

- [x] 3.1 Write RED test: `GET /api` returns 200 with JSON — implement in Phase 4
- [x] 3.2 Write RED test: `POST /api/tokens` with valid credentials returns 201 with `token` field
- [x] 3.3 Write RED test: `POST /api/tokens` with invalid credentials returns 401 JSON
- [x] 3.4 Write RED test: `GET /api/user` without token returns 401 JSON
- [x] 3.5 Write RED test: `GET /api/user` with valid token returns 200
- [x] 3.6 Write RED test: `DELETE /api/tokens/{id}` revokes and subsequent request returns 401
- [x] 3.7 Write RED test: `GET /` returns 404 (web routes removed)
- [x] 3.8 Write RED test: Validation errors on API return JSON 422
- [x] 3.9 Write RED test: 404 on non-existent API route returns JSON
- [x] 3.10 Write RED test: `/up` health check still works
- [x] 3.11 Write RED test: Application boots without node_modules/composer run test works

## Phase 4: Implementation — Wire Tests to Green

- [x] 4.1 Implement `routes/api.php` closures: JSON health response, token issuance with `Auth::attempt()`, protected `/api/user`, token revocation
- [x] 4.2 Run `php artisan test --compact` — all 10+ tests passing
- [x] 4.3 Run `vendor/bin/pint --format agent` for code style

## Phase 5: Final Verification

- [x] 5.1 Verify `composer run test` passes without npm/node_modules
- [x] 5.2 Run full test suite: `php artisan test --compact`
- [ ] 5.3 Commit all changes as a single atomic PR

## Phase 5b: Post-Verification Test Improvements

- [x] 5.4 Add test for random invalid token rejection (spec: invalid token scenario)
- [x] 5.5 Strengthen JSON body assertion for 404 API routes (assertJsonStructure ['message'])
- [x] 5.6 Replace assertStatus(422) with assertUnprocessable() in validation test
- [x] 5.7 Update Feature ExampleTest to cover distinct Content-Type check (web root returns HTML 404)

## Phase 5c: Auth Guard Default Fix

- [x] 5.8 Update `config/auth.php` default guard from `'web'` to `'api'` to remove deprecation warning
- [x] 5.9 Add test asserting `config('auth.defaults.guard')` is `'api'`

## Notes

- Task 1.1 (composer require sanctum) was technically superseded by 1.2 (install:api) which handles the composer install internally. Both ran successfully.
- Task 4.1: Used `Hash::check()` instead of `Auth::attempt()` for token issuance because Sanctum's token creation works directly with the User model, session-less. Returns 401 JSON instead of ValidationException for invalid credentials (per spec).
- Task 3.6 (revocation): Needed `app('auth')->forgetGuards()` between requests in tests because Sanctum's auth guard caches the authenticated user across HTTP requests within a single test process.
- Task 5.3: Not yet committed — pending user confirmation or PR creation.
