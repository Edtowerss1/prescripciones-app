# Prescriptions API

API REST para un sistema de prescripciones médicas con 3 roles (admin, doctor, paciente). MVP construido con Laravel como parte de una prueba técnica full-stack.

El médico crea prescripciones con ítems manuales, el paciente las consulta, marca como consumidas y descarga en PDF, y el admin visualiza métricas y gestiona usuarios.

---

## Stack

| Capa | Tecnología |
|------|-----------|
| Lenguaje | PHP 8.4 |
| Framework | Laravel 13 |
| Base de datos | PostgreSQL |
| Autenticación | Laravel Sanctum (tokens Bearer) |
| RBAC | Spatie Laravel Permission |
| Validación | Form Requests |
| Serialización | API Resources |
| PDF | barryvdh/laravel-dompdf |
| Testing | Pest 4 (162 tests, 719 assertions) |
| Formateo | Laravel Pint |

---

## Requisitos previos

- PHP 8.4+
- Composer
- PostgreSQL 15+
- Extensión PHP `pgsql`

---

## Instalación

```bash
# Clonar el repositorio
git clone <repo-url>
cd Backend

# Instalar dependencias
composer install

# Copiar variables de entorno
cp .env.example .env

# Generar clave de aplicación
php artisan key:generate

# Ejecutar migraciones
php artisan migrate

# Cargar datos de prueba
php artisan db:seed

# Iniciar servidor de desarrollo
php artisan serve
```

La API queda disponible en `http://localhost:8000/api`.

---

## Variables de entorno

Configurar en `.env`:

```env
APP_NAME="Prescriptions API"
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=prescriptions_db
DB_USERNAME=postgres
DB_PASSWORD=password

FRONTEND_URL=http://localhost:5173
SANCTUM_STATEFUL_DOMAINS=localhost:5173
SESSION_DOMAIN=localhost
```

---

## Usuarios de prueba

El seeder crea 3 usuarios con roles y perfiles:

| Rol | Email | Password |
|-----|-------|----------|
| Admin | `admin@test.com` | `admin123` |
| Doctor | `dr@test.com` | `dr123` |
| Paciente | `patient@test.com` | `patient123` |

También crea 8 prescripciones de ejemplo (4 pendientes, 4 consumidas) con 2-3 ítems cada una y 2 doctores adicionales para búsquedas.

---

## Endpoints

### Auth

| Método | Ruta | Acceso | Descripción |
|--------|------|--------|-------------|
| `POST` | `/api/auth/login` | Público | Login — retorna `access_token`, `token_type`, `user` |
| `GET` | `/api/auth/profile` | Autenticado | Perfil del usuario actual |
| `POST` | `/api/auth/logout` | Autenticado | Cierra sesión (invalida token) |

### Admin

| Método | Ruta | Acceso | Descripción |
|--------|------|--------|-------------|
| `GET` | `/api/admin/metrics?from=&to=` | Admin | Dashboard: totals, by_status, by_day, top_doctors |
| `GET` | `/api/users?role=&query=` | Admin | Listar usuarios paginado |
| `POST` | `/api/users` | Admin | Crear usuario con rol |
| `GET` | `/api/doctors?query=` | Admin | Listar doctores paginado |
| `GET` | `/api/patients?query=` | Admin | Listar pacientes paginado |
| `GET` | `/api/admin/prescriptions?status=&doctor_id=&patient_id=&from=&to=` | Admin | Listar todas las prescripciones |

### Doctor

| Método | Ruta | Acceso | Descripción |
|--------|------|--------|-------------|
| `GET` | `/api/patients?query=` | Admin, Doctor | Buscar pacientes |
| `POST` | `/api/prescriptions` | Doctor | Crear prescripción con ítems |
| `GET` | `/api/prescriptions?status=&from=&to=&order=` | Doctor | Listar sus prescripciones |
| `GET` | `/api/prescriptions/{id}` | Admin, Doctor dueño, Paciente dueño | Detalle de prescripción |
| `GET` | `/api/prescriptions/{id}/pdf` | Admin, Doctor dueño, Paciente dueño | Descargar PDF |

### Paciente

| Método | Ruta | Acceso | Descripción |
|--------|------|--------|-------------|
| `GET` | `/api/me/prescriptions?status=` | Paciente | Listar sus prescripciones |
| `PUT` | `/api/prescriptions/{id}/consume` | Paciente dueño | Marcar como consumida |

### Formato de respuesta

Éxito: `200`/`201` con `data` (recurso individual o array paginado con `meta` y `links`).

Error: envelope estándar en todos los endpoints:

```json
{
  "message": "Descripción del error",
  "code": "UNAUTHORIZED",
  "details": {}
}
```

Códigos HTTP: `200`, `201`, `204`, `401`, `403`, `404`, `409`, `422`, `429`.

### Ejemplos de requests

**Crear prescripción** (doctor):
```json
POST /api/prescriptions
Authorization: Bearer {doctor_token}
{
  "patient_id": 1,
  "notes": "Tomar con abundante agua",
  "items": [
    {
      "name": "Amoxicilina 500mg",
      "dosage": "1 cada 8 horas",
      "quantity": 15,
      "instructions": "Después de comer"
    }
  ]
}
```

**Crear usuario** (admin):
```json
POST /api/users
Authorization: Bearer {admin_token}
{
  "name": "Paciente Test",
  "email": "paciente@test.com",
  "password": "paciente123",
  "role": "patient"
}
```

---

## Decisiones técnicas

### Autenticación
Laravel Sanctum con tokens Bearer. Cada login genera un token independiente que se invalida al hacer logout (`currentAccessToken()->delete()`). El perfil del usuario autenticado se obtiene vía `GET /api/auth/profile`.

### RBAC
Spatie Laravel Permission gestiona roles (`admin`, `doctor`, `patient`). Las rutas se protegen con middleware `role:{rol}`. Las policies (`PrescriptionPolicy`, `PatientPolicy`) validan acceso a recursos específicos por ownership. Gates (`Gate::allows`) se usan en controladores para checks puntuales.

### Prescripciones
Las prescripciones se crean con código UUID automático. Items dinámicos validados vía Form Request. La transición `pending → consumed` la ejecuta `PrescriptionService` con validación de estado y timestamp `consumed_at`. Si ya está consumida, devuelve `409 Conflict`.

### PDF
Generado desde backend con `barryvdh/laravel-dompdf`. Contiene código, fecha, estado, datos del médico y paciente, notas, y tabla de ítems (nombre, dosis, cantidad, indicaciones). El acceso está controlado por la misma policy de vista.

### Paginación y filtros
Todos los listados usan `paginate()` con ordenamiento `created_at DESC` por defecto. Se soporta parámetro `order=asc|desc`. Los filtros se validan con `PrescriptionFilterRequest`. Búsqueda de pacientes/doctores por nombre vía `query` parameter con `whereHas` sobre la relación `user`.

### Errores
Manejo centralizado en `bootstrap/app.php`. Todas las excepciones (autenticación, autorización, validación, modelo no encontrado, conflicto) se renderizan con el envelope `{message, code, details}`.

### Seguridad
- CORS configurado vía `FRONTEND_URL` en `config/cors.php`
- Rate limiting: 60 req/min en auth, 120 req/min en API general, bypass en testing
- Passwords hasheadas con bcrypt automático (`'password' => 'hashed'`)
- Campo `password` y `remember_token` ocultos en serialización (`$hidden`)
- Políticas de acceso no revelan existencia de recursos (responden 404 si no hay permiso)

---

## Testing

```bash
# Ejecutar todos los tests
php artisan test --compact

# Filtrar por archivo
php artisan test --compact --filter=PrescriptionLifecycleTest

# Ejecutar tests en paralelo
php artisan test --compact --parallel
```

**Cobertura**: 162 tests, 719 assertions en ~3 segundos.

| Suite | Tests | Qué cubre |
|-------|-------|-----------|
| `ApiTest` | Login/profile/logout básico |
| `RbacAuthTest` | RBAC por rol, endpoints protegidos |
| `PrescriptionLifecycleTest` | CRUD de prescripciones, ciclo completo |
| `PrescriptionConsumeTest` | Transición pending→consumed, 409 en ya consumida |
| `PrescriptionValidationTest` | Validaciones de Form Request |
| `PrescriptionAuthorizationTest` | Acceso por ownership y rol |
| `PrescriptionPdfTest` | Generación de PDF |
| `PrescriptionListTest` | Listados con filtros y paginación |
| `PrescriptionPolicyTest` | Unit tests de políticas |
| `PatientSearchTest` | Búsqueda de pacientes |
| `PatientPolicyTest` | Unit tests de PatientPolicy |
| `PatientPrescriptionListTest` | Listado de paciente |
| `AdminMetricsTest` | Dashboard de métricas |
| `AdminPrescriptionListTest` | Listado global admin |
| `UserManagementTest` | CRUD de usuarios admin |
| `DoctorListTest` | Listado de doctores |
| `OrderParameterTest` | Parámetro order asc/desc |
| `RateLimitingTest` | Rate limiting |
| `CorsTest` | Cabeceras CORS |
| `SeederDataTest` | Validación de datos de seed |
| `ModelsSchemaTest` | Schema y relaciones |
| `ModelsFactoryTest` | Factories de modelos |

---

## Colección Postman

Una colección completa con 46 requests organizados por rol está disponible en:

```
docs/postman/prescriptions-api-full.postman_collection.json
```

Cubre flujos felices, edge cases y errores para los 18 endpoints. Usa variables de colección para tokens (auto-guardados vía scripts de login).

---

## URLs de despliegue

| Entorno | URL |
|---------|-----|
| Backend (producción) | `https://prescriptions-api.up.railway.app` |
| Backend (local) | `http://localhost:8000` |

---

## Estructura del proyecto

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── UserController.php
│   │   ├── DoctorController.php
│   │   ├── PatientController.php
│   │   ├── PrescriptionController.php
│   │   └── AdminMetricController.php
│   ├── Requests/
│   │   ├── Auth/LoginRequest.php
│   │   ├── Users/StoreUserRequest.php
│   │   └── Prescriptions/
│   │       ├── StorePrescriptionRequest.php
│   │       ├── ConsumePrescriptionRequest.php
│   │       └── PrescriptionFilterRequest.php
│   └── Resources/
│       ├── UserResource.php
│       ├── DoctorResource.php
│       ├── PatientResource.php
│       ├── PrescriptionResource.php
│       ├── PrescriptionItemResource.php
│       └── AdminMetricResource.php
├── Models/
│   ├── User.php
│   ├── Doctor.php
│   ├── Patient.php
│   ├── Prescription.php
│   └── PrescriptionItem.php
├── Services/
│   ├── UserService.php
│   ├── PrescriptionService.php
│   ├── PdfService.php
│   └── AdminMetricService.php
└── Policies/
    ├── PrescriptionPolicy.php
    └── PatientPolicy.php
```
