# Exploration: Convert Laravel Web Monolith to API-Only REST Backend

## Current State

This is a **fresh Laravel 13 skeleton** with minimal customization. The application has:

- **Routing**: Single `routes/web.php` with one route closure (`/` → `welcome` view). No `routes/api.php` exists.
- **Auth**: Session-based only (`web` guard, `session` driver in `config/auth.php`). No token auth package installed.
- **Views**: One Blade template (`resources/views/welcome.blade.php`) — the default Laravel landing page.
- **Frontend assets**: Vite + Tailwind CSS 4 configured. `resources/js/app.js` is empty (`//`). `resources/css/app.css` has basic Tailwind imports.
- **Middleware**: No custom middleware. Uses Laravel's default stack (EncryptCookies, AddQueuedCookiesToResponse, StartSession, VerifyCsrfToken, etc. applied to web routes).
- **Controllers**: Only the base `app/Http/Controllers/Controller.php` (empty abstract class).
- **Models**: Only `User.php` with standard attributes.
- **Tests**: Feature test hits `GET /` expecting 200 (will break after conversion).
- **Database**: PostgreSQL, standard migrations (users, cache, jobs). Session driver is `database`.
- **Exception handling**: Default (empty closure in `bootstrap/app.php`).
- **CSRF**: `VerifyCsrfToken` middleware active on web routes by default.

## Affected Areas

| File/Path | Why Affected | Action |
|---|---|---|
| `bootstrap/app.php` | Routing config uses `web:` only, no `api:` | Add `api:` route file, remove `web:` |
| `routes/web.php` | Web routes not needed for API-only | Delete file |
| `routes/api.php` | Does not exist | Create with API route group |
| `resources/views/welcome.blade.php` | Single view, not needed | Delete file |
| `resources/js/app.js` | Empty, not needed | Delete file |
| `resources/css/app.css` | Tailwind imports for views, not needed | Delete file |
| `vite.config.js` | References deleted JS/CSS entry points | Delete or simplify |
| `package.json` | Vite/Tailwind devDependencies not needed | Remove frontend deps |
| `config/auth.php` | `web` guard with session driver | Add `api` guard (sanctum or token) |
| `config/session.php` | Session config irrelevant for stateless API | Can keep (harmless) or remove driver |
| `.env` / `.env.example` | `SESSION_DRIVER=database` | Add API auth config vars |
| `composer.json` | `scripts.dev` runs `npm run dev` | Remove Vite from dev script |
| `composer.json` | `scripts.setup` runs `npm install` + `npm run build` | Remove npm steps |
| `tests/Feature/ExampleTest.php` | Tests `GET /` → 200 | Update or delete |
| `public/` | `index.php` entry point stays, but no assets to serve | Keep (still needed for Laravel) |
| `app/Http/Controllers/Controller.php` | Base controller stays | Keep |

## Approaches

### 1. **Minimal: Remove web layer, add API routes, keep session auth temporarily**
Remove web routes, views, and frontend build tooling. Create `routes/api.php`. Keep session-based auth for now, add token auth later as a separate change.

- **Pros**: Smallest diff, lowest risk, can be done in one commit
- **Cons**: Auth remains session-based (not truly RESTful stateless)
- **Effort**: Low

### 2. **Full: Remove web layer + add Laravel Sanctum for token auth**
Same as option 1, but also install and configure Laravel Sanctum for API token authentication in the same change.

- **Pros**: Complete API-ready stack in one change (routes + auth)
- **Cons**: Larger diff, introduces new dependency, more configuration
- **Effort**: Medium

### 3. **Full + API Resources: Remove web + Sanctum + create API Resource pattern**
Option 2 plus establish API Resource classes, exception rendering to JSON, and API response conventions.

- **Pros**: Production-ready API foundation with proper response formatting
- **Cons**: Largest scope, more decisions to make (resource naming, error format, etc.)
- **Effort**: Medium-High

## Recommendation

**Approach 2** (Full: Remove web layer + Sanctum) is the best balance. The skeleton is so minimal that doing auth setup now avoids a second change immediately after. Sanctum is the Laravel-recommended approach for SPA/mobile API auth and integrates cleanly.

However, if the user wants the smallest possible change first, **Approach 1** is perfectly valid given how clean the skeleton is — there's almost nothing to break.

## Detailed Change Plan

### Files to DELETE:
1. `routes/web.php`
2. `resources/views/welcome.blade.php`
3. `resources/js/app.js`
4. `resources/css/app.css`
5. `vite.config.js`
6. `package.json` (or strip to empty/minimal)

### Files to CREATE:
1. `routes/api.php` — with `Route::apiResource()` prefix group
2. Sanctum migration + config (if Approach 2)

### Files to MODIFY:
1. `bootstrap/app.php` — change `withRouting()` from `web:` to `api:`, add exception handler that forces JSON
2. `config/auth.php` — add `api` guard
3. `composer.json` — remove npm from scripts, optionally remove `laravel/tinker` if not needed
4. `.env.example` — update session/auth defaults
5. `tests/Feature/ExampleTest.php` — update to test API endpoint

### Files to KEEP (unchanged):
- `public/index.php` — still the entry point
- `public/.htaccess`, `public/favicon.ico`, `public/robots.txt`
- `app/Http/Controllers/Controller.php`
- `app/Models/User.php`
- `routes/console.php`
- `config/session.php` — harmless to keep, may be needed for Sanctum's session-based SPA auth
- All database migrations
- `config/cache.php`, `config/database.php`, `config/queue.php`, `config/mail.php`, `config/filesystems.php`, `config/logging.php`, `config/services.php`, `config/app.php`

## Risks

1. **Breaking the existing test**: `tests/Feature/ExampleTest.php` tests `GET /` which will return 404 after removing web routes. Must update test.
2. **CSRF middleware**: When switching to API routes, CSRF verification is NOT applied by default (correct behavior). No risk here.
3. **Exception rendering**: Default Laravel exceptions return HTML error pages. API routes need JSON responses. Must configure exception handler to force JSON for API routes (or use `Accept: application/json` header).
4. **Session config**: Even for API-only, session config can stay. Sanctum's SPA auth mode actually uses sessions. No data loss risk.
5. **No existing domain code**: This is a fresh skeleton — no business logic to migrate. Risk is minimal.
6. **Frontend build in CI/CD**: If any CI pipeline runs `npm install` or `npm run build`, it will fail after removing `package.json`. Update CI config if it exists.

## Ready for Proposal

**Yes.** The codebase is a clean Laravel 13 skeleton with minimal customization. The scope is well-understood, risks are low, and the recommended approach is clear. The orchestrator should proceed to `sdd-propose` to formalize the change intent, scope, and approach.
