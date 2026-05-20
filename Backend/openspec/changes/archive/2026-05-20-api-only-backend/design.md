# Design: Convert Laravel App to API-Only REST Backend

## Technical Approach

Bootstrap the API foundation through `php artisan install:api` (installs Sanctum, scaffolds `routes/api.php`, adds guard config), then surgically customize: add an `api`-named guard, wire JSON-first exception rendering, remove all web/Blade/Vite/npm artifacts, and rewrite composer scripts. The User model gains `HasApiTokens`. Token issuance and revocation use dedicated controller closures to keep the change contained.

## Architecture Decisions

| Decision | Options | Tradeoffs | Verdict |
|----------|---------|-----------|---------|
| Sanctum setup method | A) `php artisan install:api` + customize, B) Manual composer require + all config by hand | A: canonical, publishes migrations/config correctly, less error-prone. B: full control but risk of missing a config key. | **A** — use the command then refine |
| Guard name | A) `api` with `sanctum` driver, B) `sanctum` with `sanctum` driver | A: matches spec expectation (`auth('api')`). B: matches framework default but needs middleware rename from `auth:sanctum` to `auth:api` anyway. | **A** — aligns with spec |
| JSON exception rendering | A) `shouldRenderJsonWhen($request->is('api/*'))`, B) `$request->expectsJson()` | A: explicit, always JSON for API routes regardless of `Accept` header. B: depends on client sending correct Accept header. | **A** — spec requires JSON first |
| Token controller location | A) Inline closures in `routes/api.php`, B) Dedicated `Api/TokenController` | A: minimal, appropriate for skeleton. B: better testability and separation for future growth. | **A** for now — extract later when CRUD grows |
| Frontend removal scope | A) Delete `resources/{views,js,css}`, `vite.config.js`, `package.json`, `.npmrc`, B) Keep package.json stripped | A: no npm surface at all. B: mixed signal about project intent. | **A** — fully clean |

## Data Flow

```
 Client ──Bearer Token──→ routes/api.php
                              │
            ┌─────────────────┼─────────────────┐
            ▼                 ▼                  ▼
     GET /api           POST /api/tokens    DELETE /api/tokens/{id}
     (public)           (auth: password)    (auth:api token)
            │                 │                  │
            ▼                 ▼                  ▼
      JSON {status}    HasApiTokens          token->delete()
                       createToken()
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `composer.json` | Modify | Add `laravel/sanctum`; remove `setup` and rewrite `dev` scripts |
| `config/auth.php` | Modify | Add `api` guard with `sanctum` driver; set as default |
| `bootstrap/app.php` | Modify | Route `api.php` and `console.php` only; add `shouldRenderJsonWhen('api/*')` |
| `routes/api.php` | Create | Public health route, token issuance, protected user route, token revocation |
| `routes/web.php` | Delete | No web routes in API-only mode |
| `app/Models/User.php` | Modify | Add `HasApiTokens` trait from Sanctum |
| `resources/views/welcome.blade.php` | Delete | No Blade rendering needed |
| `resources/css/app.css` | Delete | No CSS assets needed |
| `resources/js/app.js` | Delete | No JS assets needed |
| `vite.config.js` | Delete | No frontend build tooling |
| `package.json` | Delete | No npm dependencies |
| `.npmrc` | Delete | No npm configuration |
| `tests/Feature/ExampleTest.php` | Modify | Rewrite for API endpoint assertions |

## Interfaces

**POST `/api/tokens`** (public, email+password auth):
```
Request:  { "email": "...", "password": "..." }
201:      { "token": "1|plain-text-token" }
401:      { "message": "Invalid credentials" }
```

**DELETE `/api/tokens/{id}`** (auth:api):
```
204:      No content
401:      { "message": "Unauthenticated" }
```

## Testing Strategy

| Layer | What | Approach |
|-------|------|----------|
| Feature | API root returns JSON | `$this->get('/api')->assertJson(...)` |
| Feature | Web root returns 404 | `$this->get('/')->assertNotFound()` |
| Feature | Health check `/up` still works | `$this->get('/up')->assertOk()` |
| Feature | Token issuance valid/invalid credentials | Factory user, POST `/api/tokens` |
| Feature | Protected route rejects unauthenticated | GET `/api/user` without token → 401 |
| Feature | Token revocation | Create token, DELETE it, verify 401 after |
| Feature | Validation errors return JSON 422 | POST malformed data |
| Feature | 404 errors return JSON | GET non-existent API route |

**Config**: `strict_tdd: true` — write failing tests first, then implement. Commands are per `openspec/config.yaml`.

## Rollout

No data migration required. Change is atomic — all file changes committed together. Rollback restores `routes/web.php`, `vite.config.js`, `package.json`, frontend assets, and original `bootstrap/app.php`/`composer.json`.
