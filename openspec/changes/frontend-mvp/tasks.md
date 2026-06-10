# Tasks: Frontend MVP

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~3,500 |
| 400-line budget risk | High |
| Chained PRs recommended | Yes |
| Delivery strategy | ask-on-risk |
| Chain strategy | pending |

Decision needed before apply: Yes (resolved: feature-branch-chain)
Chained PRs recommended: Yes
Chain strategy: feature-branch-chain
400-line budget risk: High

### Suggested Work Units

| Unit | Goal | Est. Lines | Depends On |
|------|------|-----------|------------|
| 1 | Project scaffold + config + types + API client | ~275 | — |
| 2 | Auth store + router + guards + useAuth | ~270 | Unit 1 |
| 3 | Layouts + LoginView + Form UI (Button/Input/Select) | ~460 | Unit 2 |
| 4 | Display UI (Table/Modal/Toast/Spinner/Empty) + StatusBadge | ~335 | Unit 1 |
| 5 | Doctor API + store + composables + Prescription components | ~550 | Units 3, 4 |
| 6 | Doctor views + Patient views | ~560 | Unit 5 |
| 7 | Admin API + store + Charts + Admin views + Tests + README | ~790 | Units 2, 4 |

Units 3, 5, 6, 7 exceed 400 lines each. If strict <400 slicing is enforced, they sub-split as:
- Unit 3 → 3a (Layouts+LoginView ~290) + 3b (Form UI ~170)
- Unit 5 → 5a (API+store+composables ~330) + 5b (Prescription components ~220)
- Unit 6 → 6a (Doctor list+detail ~270) + 6b (Doctor create + Patient views ~290)
- Unit 7 → 7a (Admin API+store ~130) + 7b (Charts ~150) + 7c (Admin views ~330) + 7d (Tests+README ~355)

## Phase 1: Project Scaffolding + Foundation (17 tasks) ✅ COMPLETE

- [x] 1.1 Create `frontend/package.json` with all deps (vue, vue-router, pinia, axios, chart.js, tailwindcss, vite, vitest)
- [x] 1.2 Create `frontend/vite.config.ts` Vue plugin + TailwindCSS + `@/` alias
- [x] 1.3 Create `frontend/tsconfig.json` strict mode + path aliases
- [x] 1.4 Create `frontend/index.html` app mount
- [x] 1.5 Create `frontend/postcss.config.js`
- [x] 1.6 Create `frontend/env.d.ts` for VITE_API_BASE_URL
- [x] 1.7 Create `frontend/.env` and `frontend/.env.example`
- [x] 1.8 Create `frontend/src/main.ts` app bootstrap
- [x] 1.9 Create `frontend/src/App.vue` RouterView + ToastContainer
- [x] 1.10 Create `frontend/src/style.css` Tailwind directives
- [x] 1.11 Create `frontend/src/api/client.ts` Axios + interceptors (Bearer, 401→logout, 422→throw)
- [x] 1.12 Create `frontend/src/types/auth.ts` LoginRequest, LoginResponse
- [x] 1.13 Create `frontend/src/types/user.ts` User interface
- [x] 1.14 Create `frontend/src/types/prescription.ts` Prescription, PrescriptionItem, CreatePrescriptionPayload
- [x] 1.15 Create `frontend/src/types/doctor.ts` Doctor
- [x] 1.16 Create `frontend/src/types/patient.ts` Patient
- [x] 1.17 Create `frontend/src/types/api.ts` PaginatedResponse, ApiError, ValidationError

## Phase 2: Auth Shell + Router + Layout (8 tasks)

### PR #2a — `feat/frontend-auth-shell` → `feat/frontend-scaffold` (217 lines ✅)
- [x] 2.1 Create `frontend/src/api/auth.api.ts` login/profile/logout
- [x] 2.2 Create `frontend/src/stores/auth.store.ts` token+user state, login/logout/fetchProfile, getters
- [x] 2.3 Create `frontend/src/composables/useAuth.ts`
- [x] 2.4 Create `frontend/src/router/index.ts` role-lazy routes: login, doctor/*, patient/*, admin/*
- [x] 2.5 Create `frontend/src/router/guards.ts` beforeEach: auth check → profile → role match → redirect

### PR #2b — `feat/frontend-auth-layout` → `feat/frontend-auth-shell` (281 lines ✅)
- [x] 2.6 Create `frontend/src/layouts/AuthLayout.vue` centered card
- [x] 2.7 Create `frontend/src/layouts/DashboardLayout.vue` role-aware sidebar + top bar
- [x] 2.8 Create `frontend/src/views/LoginView.vue` form, loading, inline errors, role redirect

## Phase 3: UI Foundation Components (10 tasks) ✅ COMPLETE

- [x] 3.1-3.9 Create BaseButton, BaseInput, BaseSelect, BaseTable, BaseModal, BaseToast, ToastContainer, EmptyState, LoadingSpinner in `frontend/src/components/ui/`
- [x] 3.10 Create `frontend/src/components/prescriptions/PrescriptionStatusBadge.vue` pending→amber, consumed→green

## Phase 4: Doctor Workflow (11 tasks) ✅ COMPLETE

- [x] 4.1 Create `frontend/src/api/patients.api.ts` list(query)
- [x] 4.2 Create `frontend/src/api/prescriptions.api.ts` list/create/show/consume/pdf
- [x] 4.3 Create `frontend/src/api/doctors.api.ts` list(query)
- [x] 4.4 Create `frontend/src/stores/prescriptions.store.ts` list, detail, filters, pagination, actions
- [x] 4.5 Create `frontend/src/composables/usePagination.ts` page state, meta.* parsing
- [x] 4.6 Create `frontend/src/composables/useFilters.ts` reactive filter, reset(), toQueryParams()
- [x] 4.7 Create `frontend/src/components/prescriptions/PrescriptionForm.vue` patient selector + notes + items
- [x] 4.8 Create `frontend/src/components/prescriptions/PrescriptionItemsForm.vue` add/remove items
- [x] 4.9 Create `frontend/src/components/prescriptions/PrescriptionTable.vue` BaseTable wrapper
- [x] 4.10 Create `frontend/src/views/doctor/DoctorPrescriptionsView.vue` table + filters + create button
- [x] 4.11 Create `frontend/src/views/doctor/DoctorCreatePrescriptionView.vue` + DoctorPrescriptionDetailView.vue + router verify

## Phase 5: Patient Workflow (2 tasks) ✅ COMPLETE

- [x] 5.1 Create `frontend/src/views/patient/PatientPrescriptionsView.vue` table + consume/PDF actions
- [x] 5.2 Create `frontend/src/views/patient/PatientPrescriptionDetailView.vue` detail + consume + PDF

## Phase 6: Admin Dashboard (8 tasks)

- [x] 6.1 Create `frontend/src/api/doctors.api.ts` list(query) *(completed in Phase 4)*
- [x] 6.2 Create `frontend/src/api/admin.api.ts` metrics(), prescriptions()
- [x] 6.3 Create `frontend/src/stores/admin.store.ts` metrics + presc list + filters
- [x] 6.4-6.6 Create PrescriptionsByStatusChart, PrescriptionsByDayChart, TopDoctorsChart in `frontend/src/components/charts/`
- [x] 6.7 Create `frontend/src/views/admin/AdminDashboardView.vue` metric cards + 3 charts
- [x] 6.8 Create `frontend/src/views/admin/AdminPrescriptionsView.vue` global table + filters

## Phase 7: Frontend Tests + Polish (6 tasks)

- [ ] 7.1 Configure vitest with jsdom environment
- [ ] 7.2 Write auth store unit test: login/logout/fetchProfile state transitions
- [ ] 7.3 Write LoginView integration test: submit, loading, error, role redirect
- [ ] 7.4 Write DoctorCreatePrescriptionView test: validation + items management
- [ ] 7.5 Responsive refinements across all views
- [ ] 7.6 Final README: install, env vars, test accounts, endpoints, architecture decisions
