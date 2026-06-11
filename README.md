# Prescripciones App

Full-stack prescription management system built with Laravel (backend) and Vue 3 (frontend).

## Table of Contents

- [Architecture](#architecture)
- [Backend](#backend)
- [Frontend](#frontend)
  - [Prerequisites](#prerequisites)
  - [Installation](#installation)
  - [Environment Variables](#environment-variables)
  - [Development Server](#development-server)
  - [Build](#build)
  - [Test Commands](#test-commands)
  - [Test Credentials](#test-credentials)
  - [Tech Stack](#tech-stack)
  - [Project Structure](#project-structure)

## Architecture

Monolithic Laravel backend serving a JSON API consumed by a Vue 3 SPA frontend.

```
┌─────────────┐     HTTP/JSON      ┌──────────────┐
│  Vue 3 SPA  │ ──────────────────→ │  Laravel API │
│  (frontend) │ ←────────────────── │  (backend)   │
└─────────────┘    Bearer Token     └──────────────┘
```

### Key Design Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Auth persistence | localStorage | Simplest MVP path with Sanctum token auth |
| State management | Single Pinia stores | Backend already scopes by role; store methods gate via `user.role` |
| Chart data flow | Parent fetches, passes props | Charts stay pure presentational |
| Scaffold | Manual Vite setup | Full control, no generated cleanup |
| PDF download | Axios blob + ObjectURL | Must send Bearer token |
| Role model | Spatie Permission (`spatie/laravel-permission`) | RBAC via `HasRoles` trait; no `users.role` column — intentional design decision |

## Backend

See `Backend/` directory. Laravel application with:

- Sanctum token authentication
- Role-based access control (admin, doctor, patient)
- Prescription CRUD with PDF generation

```bash
cd Backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

### API Endpoints

| Method | Endpoint | Auth | Roles | Description |
|--------|----------|------|-------|-------------|
| `GET` | `/api` | — | Public | Health check |
| `POST` | `/api/auth/login` | Throttle (60/min) | Public | Login, returns token + user |
| `GET` | `/api/auth/profile` | `auth:sanctum` | All | Authenticated user profile |
| `POST` | `/api/auth/logout` | `auth:sanctum` | All | Revoke current token |
| `GET` | `/api/admin/metrics` | `auth:sanctum` + `role:admin` | Admin | Dashboard metrics |
| `GET` | `/api/admin/prescriptions` | `auth:sanctum` + `role:admin` | Admin | List all prescriptions |
| `GET` | `/api/users` | `auth:sanctum` + `role:admin` | Admin | List users |
| `POST` | `/api/users` | `auth:sanctum` + `role:admin` | Admin | Create user |
| `GET` | `/api/doctors` | `auth:sanctum` + `role:admin` | Admin | List doctors |
| `GET` | `/api/patients` | `auth:sanctum` + `role:admin\|doctor` | Admin, Doctor | List patients |
| `POST` | `/api/prescriptions` | `auth:sanctum` + `role:doctor` | Doctor | Create prescription |
| `GET` | `/api/prescriptions` | `auth:sanctum` + `role:doctor` | Doctor | List own prescriptions |
| `GET` | `/api/prescriptions/{id}` | `auth:sanctum` | Policy-gated | Prescription detail |
| `GET` | `/api/prescriptions/{id}/pdf` | `auth:sanctum` | Policy-gated | Download PDF |
| `PUT` | `/api/prescriptions/{id}/consume` | `auth:sanctum` | Policy-gated | Mark as consumed |
| `GET` | `/api/me/prescriptions` | `auth:sanctum` + `role:patient` | Patient | List own prescriptions |

> **Deployment:** URL and base domain are pending deployment — update `VITE_API_BASE_URL` and the backend base URL when a server is provisioned.

## Frontend

Single-page application built with Vue 3, TypeScript, Pinia, and TailwindCSS.

### Prerequisites

- **Node.js 18+** (LTS recommended)
- **npm 9+**

### Installation

```bash
cd frontend
npm install
```

### Environment Variables

Create `frontend/.env` (or copy from `.env.example`):

```env
VITE_API_BASE_URL=http://localhost:8000/api
```

| Variable | Default | Description |
|----------|---------|-------------|
| `VITE_API_BASE_URL` | `http://localhost:8000/api` | Backend API base URL |

### Development Server

```bash
npm run dev
```

Serves at `http://localhost:5173`. The backend must be running at `VITE_API_BASE_URL`.

### Build

```bash
npm run build
```

Outputs to `frontend/dist/`. TypeScript type-checking runs as part of the build via `vue-tsc`.

### Test Commands

```bash
# Run all tests (single run)
npm run test

# Watch mode
npm run test:watch
```

Tests use **Vitest** with jsdom environment and `@vue/test-utils`.

### Test Credentials

After seeding the database (`php artisan db:seed`):

| Role | Email | Password |
|------|-------|----------|
| Admin | `admin@test.com` | `admin123` |
| Doctor | `dr@test.com` | `dr123` |
| Patient | `patient@test.com` | `patient123` |

### Tech Stack

| Category | Technology |
|----------|------------|
| Framework | Vue 3 (Composition API + `<script setup>`) |
| Language | TypeScript (strict mode) |
| Build tool | Vite 6 |
| State management | Pinia |
| Router | Vue Router 4 (lazy-loaded role routes) |
| HTTP client | Axios (Bearer interceptor, unified error handling) |
| Charts | Chart.js 4 |
| Styling | TailwindCSS v4 |
| Testing | Vitest + jsdom + @vue/test-utils |

### Project Structure

```
frontend/
├── src/
│   ├── api/                # API modules (auth, patients, prescriptions, doctors, admin)
│   │   ├── client.ts       # Axios instance with interceptors
│   │   ├── auth.api.ts      # login, profile, logout
│   │   ├── patients.api.ts  # list patients
│   │   ├── prescriptions.api.ts  # CRUD + consume + PDF
│   │   ├── doctors.api.ts   # list doctors
│   │   └── admin.api.ts     # metrics, global prescriptions
│   ├── components/
│   │   ├── ui/              # Reusable UI primitives (Button, Input, Select, Table, Modal, Toast, etc.)
│   │   ├── prescriptions/   # Prescription-specific components (Form, ItemsForm, Table, StatusBadge)
│   │   └── charts/          # Chart.js wrappers (ByStatus, ByDay, TopDoctors)
│   ├── composables/         # Shared composables (useAuth, usePagination, useFilters, useToast)
│   ├── layouts/             # AuthLayout (centered card), DashboardLayout (sidebar + top bar)
│   ├── router/
│   │   ├── index.ts         # Route definitions (lazy-loaded by role)
│   │   └── guards.ts        # beforeEach guard: auth check → profile → role redirect
│   ├── stores/              # Pinia stores (auth, prescriptions, admin)
│   ├── types/               # TypeScript interfaces matching backend API resources
│   ├── views/
│   │   ├── LoginView.vue
│   │   ├── doctor/          # Doctor CRUD views
│   │   ├── patient/         # Patient read + consume views
│   │   └── admin/           # Admin dashboard + global prescriptions
│   ├── test-setup.ts        # Vitest setup file
│   ├── main.ts              # App bootstrap
│   ├── App.vue              # Root component (RouterView + Toast)
│   └── style.css            # TailwindCSS directives
├── index.html
├── vite.config.ts
├── tsconfig.json
└── package.json
```

### Routes

| Path | Role | Component |
|------|------|-----------|
| `/login` | Public | LoginView |
| `/doctor/prescriptions` | Doctor | DoctorPrescriptionsView |
| `/doctor/prescriptions/new` | Doctor | DoctorCreatePrescriptionView |
| `/doctor/prescriptions/:id` | Doctor | DoctorPrescriptionDetailView |
| `/patient/prescriptions` | Patient | PatientPrescriptionsView |
| `/patient/prescriptions/:id` | Patient | PatientPrescriptionDetailView |
| `/admin` | Admin | AdminDashboardView |
| `/admin/prescriptions` | Admin | AdminPrescriptionsView |
| `/admin/prescriptions/:id` | Admin | DoctorPrescriptionDetailView |
| `/:catchAll(.*)` | — | Redirect to `/login` |

### State Coverage

Every data-driven view handles these states:

- **Loading**: Spinner/skeleton on initial fetch
- **Empty**: Descriptive message (with CTA where applicable)
- **Error**: Error message with retry button
- **Success feedback**: Toast notification for mutations
