PRUEBA TÉCNICA FULL STACK — APP DE PRESCRIPCIONES

Stack propuesto

Backend:
Laravel API + Eloquent ORM + PostgreSQL.
Autenticación con Laravel Sanctum.
RBAC con Middlewares, Policies y Gates.
Validaciones con Form Requests.
Serialización con API Resources.
Generación de PDF desde backend.

Frontend:
Vue 3 + Vite + TypeScript.
Vue Router.
Pinia para manejo de estado.
TailwindCSS.
Axios para consumo de API.
Chart.js o ApexCharts para métricas.

Infra sugerida:
Backend en Railway / Render.
Base de datos en Railway PostgreSQL.
Frontend en Vercel / Netlify.


Objetivo

Construir un MVP simple y sólido de un sistema de prescripciones médicas con 3 roles:

- Médico.
- Paciente.
- Admin.

El médico crea prescripciones asociadas a un paciente con ítems digitados manualmente.

No hay CRUD de productos.

El paciente ve sus prescripciones, puede marcarlas como consumidas y descargarlas en PDF.

El admin visualiza métricas generales, totales y datos por estado.


1. Requerimientos funcionales

Roles

Admin:

- Visualiza métricas generales del sistema:
  - Número de pacientes.
  - Número de médicos.
  - Número de prescripciones.
  - Prescripciones por estado.
  - Prescripciones por día.
  - Top médicos por volumen de prescripciones.

- Opcional / Plus:
  - Crear usuarios.
  - Asignar roles.
  - Consultar todas las prescripciones del sistema.


Médico:

- Crea prescripciones para un paciente existente.
- Puede buscar pacientes.
- Lista sus propias prescripciones.
- Ve el detalle de sus prescripciones.
- Filtra sus prescripciones por estado y fecha.


Paciente:

- Lista sus prescripciones.
- Ve el detalle de cada prescripción.
- Cambia el estado de una prescripción de pendiente a consumida.
- Descarga el PDF de la prescripción.


Flujo mínimo

1. El usuario inicia sesión con email y contraseña.
2. El sistema identifica el rol del usuario.
3. El médico crea una prescripción para un paciente.
4. El paciente visualiza su bandeja de prescripciones.
5. El paciente marca una prescripción como consumida.
6. El paciente descarga la prescripción en PDF.
7. El admin visualiza métricas del sistema.


Estados de una prescripción

- pending
- consumed


Ítems de la prescripción

No requieren estado propio.

Cada ítem debe contener:

- Nombre.
- Dosis.
- Cantidad.
- Indicaciones.


2. Requerimientos técnicos

Autenticación / Autorización

- Autenticación mediante Laravel Sanctum.
- Login por email y password.
- Tokens Bearer para comunicación entre Vue y Laravel API.
- Endpoint para obtener perfil del usuario autenticado.
- Logout invalidando token actual.
- Protección de rutas por rol.

Roles permitidos:

- admin
- doctor
- patient


RBAC

El control de acceso debe implementarse con:

- Middleware de roles.
- Policies para validar acceso a recursos específicos.
- Gates si aplica.

Ejemplo:

- Un médico sólo puede ver sus propias prescripciones.
- Un paciente sólo puede ver sus propias prescripciones.
- Un admin puede ver toda la información.


Validación y seguridad

Backend:

- Validación con Form Requests.
- Respuestas JSON consistentes.
- Manejo centralizado de errores.
- Uso correcto de códigos HTTP.
- Protección con CORS.
- Rate limiting básico.
- Hash de contraseñas con bcrypt.
- Evitar exponer datos sensibles.
- Uso de API Resources para controlar respuestas.

Frontend:

- Protección de rutas por rol.
- Manejo de tokens.
- Manejo de estados de carga.
- Manejo de errores.
- Redirección si el usuario no está autenticado.
- Redirección si el usuario no tiene permisos.


Base de datos

- PostgreSQL.
- Laravel Migrations.
- Laravel Seeders.
- Relaciones correctamente definidas con Eloquent.
- Índices en campos frecuentes de búsqueda.
- Paginación en listados.
- Filtros por estado y fecha.
- Ordenamiento por fecha de creación descendente por defecto.


Features mínimas

- Login.
- Perfil autenticado.
- Roles.
- Creación de prescripciones.
- Ítems dinámicos.
- Listado de prescripciones.
- Detalle de prescripción.
- Marcar prescripción como consumida.
- Descargar PDF.
- Dashboard administrativo.
- Filtros.
- Paginación.
- Seeders con usuarios de prueba.


Testing

Backend:

- Tests básicos con PHPUnit o Pest.
- Al menos pruebas para:
  - Login.
  - Creación de prescripción.
  - Restricción por rol.
  - Consumo de prescripción.

Frontend:

- Prueba mínima de componente o store crítico.
- Opcional:
  - Vitest.
  - Vue Testing Library.


3. Modelado de base de datos

Tablas principales:

users
doctors
patients
prescriptions
prescription_items


Tabla users

Campos:

- id
- name
- email
- password
- role
- created_at
- updated_at

Roles permitidos:

- admin
- doctor
- patient


Tabla doctors

Campos:

- id
- user_id
- specialty
- created_at
- updated_at

Relación:

- Un doctor pertenece a un usuario.
- Un doctor tiene muchas prescripciones.


Tabla patients

Campos:

- id
- user_id
- birth_date
- created_at
- updated_at

Relación:

- Un paciente pertenece a un usuario.
- Un paciente tiene muchas prescripciones.


Tabla prescriptions

Campos:

- id
- code
- status
- notes
- patient_id
- doctor_id
- consumed_at
- created_at
- updated_at

Estados:

- pending
- consumed

Relaciones:

- Una prescripción pertenece a un paciente.
- Una prescripción pertenece a un médico.
- Una prescripción tiene muchos ítems.


Tabla prescription_items

Campos:

- id
- prescription_id
- name
- dosage
- quantity
- instructions
- created_at
- updated_at

Relación:

- Un ítem pertenece a una prescripción.


Índices sugeridos

- prescriptions(status, created_at)
- prescriptions(patient_id)
- prescriptions(doctor_id)
- prescriptions(code)
- users(email)
- users(role)


4. Modelos Laravel sugeridos

User.php

Relaciones:

- doctor()
- patient()

Doctor.php

Relaciones:

- user()
- prescriptions()

Patient.php

Relaciones:

- user()
- prescriptions()

Prescription.php

Relaciones:

- patient()
- doctor()
- items()

PrescriptionItem.php

Relaciones:

- prescription()


5. API: contratos mínimos

Auth

POST /api/auth/login

Body:

{
  "email": "dr@test.com",
  "password": "dr123"
}

Respuesta:

{
  "access_token": "...",
  "token_type": "Bearer",
  "user": {
    "id": 1,
    "name": "Doctor Test",
    "email": "dr@test.com",
    "role": "doctor"
  }
}


GET /api/auth/profile

Respuesta:

{
  "id": 1,
  "name": "Doctor Test",
  "email": "dr@test.com",
  "role": "doctor"
}


POST /api/auth/logout

Cierra la sesión eliminando el token actual.


Usuarios

GET /api/users?role=doctor|patient&query=

Listado paginado de usuarios.

Sólo Admin.


POST /api/users

Crea usuario con rol.

Sólo Admin.

Body:

{
  "name": "Paciente Test",
  "email": "patient@test.com",
  "password": "patient123",
  "role": "patient"
}


Pacientes

GET /api/patients?query=&page=&limit=

Listado paginado de pacientes.

Acceso:

- Admin.
- Médico.


Doctores

GET /api/doctors?query=&page=&limit=

Listado paginado de doctores.

Acceso:

- Admin.


Prescripciones - Médico

POST /api/prescriptions

Crea una prescripción.

Acceso:

- Médico.

Body:

{
  "patient_id": 1,
  "notes": "Tomar con abundante agua.",
  "items": [
    {
      "name": "Amoxicilina 500mg",
      "dosage": "1 cada 8 horas",
      "quantity": 15,
      "instructions": "Después de comer"
    }
  ]
}


GET /api/prescriptions?status=&from=&to=&page=&limit=&order=

Lista las prescripciones del médico autenticado.

Acceso:

- Médico.

Filtros:

- status
- from
- to
- page
- limit
- order


GET /api/prescriptions/{id}

Detalle de una prescripción.

Acceso:

- Médico dueño de la prescripción.
- Paciente dueño de la prescripción.
- Admin.


Prescripciones - Paciente

GET /api/me/prescriptions?status=&page=&limit=

Lista las prescripciones del paciente autenticado.

Acceso:

- Paciente.


PUT /api/prescriptions/{id}/consume

Marca una prescripción como consumida.

Acceso:

- Paciente dueño de la prescripción.

Regla:

- Sólo puede pasar de pending a consumed.
- Debe llenar consumed_at.


GET /api/prescriptions/{id}/pdf

Descarga el PDF de la prescripción.

Acceso:

- Paciente dueño de la prescripción.
- Médico dueño de la prescripción.
- Admin.


Prescripciones - Admin

GET /api/admin/prescriptions?status=&doctor_id=&patient_id=&from=&to=&page=&limit=

Lista todas las prescripciones con filtros.

Acceso:

- Admin.


Métricas Admin

GET /api/admin/metrics?from=&to=

Acceso:

- Admin.

Respuesta:

{
  "totals": {
    "doctors": 10,
    "patients": 120,
    "prescriptions": 560
  },
  "by_status": {
    "pending": 120,
    "consumed": 440
  },
  "by_day": [
    {
      "date": "2026-05-20",
      "count": 20
    }
  ],
  "top_doctors": [
    {
      "doctor_id": 1,
      "doctor_name": "Dr. Juan Pérez",
      "count": 50
    }
  ]
}


6. Reglas de acceso

Admin:

- Puede ver usuarios.
- Puede ver doctores.
- Puede ver pacientes.
- Puede ver todas las prescripciones.
- Puede ver métricas.
- Opcionalmente puede crear usuarios.


Médico:

- Puede ver pacientes.
- Puede crear prescripciones.
- Sólo puede ver sus propias prescripciones.
- Puede descargar PDF de sus propias prescripciones.


Paciente:

- Sólo puede ver sus propias prescripciones.
- Puede marcar sus prescripciones como consumidas.
- Puede descargar PDF de sus prescripciones.


7. Respuestas de error

Formato estándar:

{
  "message": "No autorizado",
  "code": "UNAUTHORIZED",
  "details": {}
}

Códigos HTTP esperados:

- 200 OK.
- 201 Created.
- 400 Bad Request.
- 401 Unauthorized.
- 403 Forbidden.
- 404 Not Found.
- 409 Conflict.
- 422 Unprocessable Entity.
- 500 Internal Server Error.


8. Frontend Vue 3

Tecnologías:

- Vue 3.
- Vite.
- TypeScript.
- Vue Router.
- Pinia.
- TailwindCSS.
- Axios.
- Chart.js o ApexCharts.


Páginas mínimas

Autenticación

/login

Debe incluir:

- Email.
- Password.
- Manejo de error.
- Estado de carga.
- Redirección según rol.


Médico

/doctor/prescriptions

Debe incluir:

- Listado de prescripciones.
- Filtro por estado.
- Filtro por fecha.
- Paginación.
- Botón para crear nueva prescripción.


/doctor/prescriptions/new

Debe incluir:

- Selector o buscador de paciente.
- Campo de notas.
- Ítems dinámicos.
- Botón para agregar ítem.
- Botón para eliminar ítem.
- Validaciones.
- Toast de éxito o error.


/doctor/prescriptions/:id

Debe incluir:

- Datos del paciente.
- Datos del médico.
- Código.
- Fecha.
- Estado.
- Notas.
- Lista de ítems.
- Botón para descargar PDF.


Paciente

/patient/prescriptions

Debe incluir:

- Listado de prescripciones.
- Filtro por estado.
- Paginación.
- Acción para marcar como consumida.
- Acción para descargar PDF.


/patient/prescriptions/:id

Debe incluir:

- Detalle de la prescripción.
- Estado.
- Ítems.
- Botón para marcar consumida si está pendiente.
- Botón para descargar PDF.


Admin

/admin

Dashboard con:

- Total de doctores.
- Total de pacientes.
- Total de prescripciones.
- Prescripciones por estado.
- Serie por día.
- Top médicos por volumen.


/admin/prescriptions

Opcional.

Debe incluir:

- Listado global de prescripciones.
- Filtros por estado.
- Filtros por médico.
- Filtros por paciente.
- Filtros por fecha.
- Paginación.


9. UX/UI

La interfaz debe ser:

- Responsive.
- Clara.
- Moderna.
- Basada en cards, tablas y formularios limpios.
- Con estados de carga.
- Con estados vacíos.
- Con mensajes de error.
- Con toasts para acciones importantes.

Estados esperados:

- Cargando datos.
- Sin resultados.
- Error de servidor.
- Acción completada.
- Usuario no autorizado.


10. Estructura sugerida del backend

backend/

app/
  Http/
    Controllers/
      AuthController.php
      UserController.php
      DoctorController.php
      PatientController.php
      PrescriptionController.php
      AdminMetricController.php

    Requests/
      Auth/
        LoginRequest.php

      Users/
        StoreUserRequest.php

      Prescriptions/
        StorePrescriptionRequest.php
        ConsumePrescriptionRequest.php
        PrescriptionFilterRequest.php

    Resources/
      UserResource.php
      DoctorResource.php
      PatientResource.php
      PrescriptionResource.php
      PrescriptionItemResource.php
      AdminMetricResource.php

    Middleware/
      RoleMiddleware.php

  Models/
    User.php
    Doctor.php
    Patient.php
    Prescription.php
    PrescriptionItem.php

  Services/
    AuthService.php
    PrescriptionService.php
    PdfService.php
    AdminMetricService.php

  Policies/
    PrescriptionPolicy.php

database/
  migrations/
  seeders/
    DatabaseSeeder.php
    UserSeeder.php
    PrescriptionSeeder.php

routes/
  api.php


11. Estructura sugerida del frontend

frontend/

src/
  api/
    axios.ts
    auth.api.ts
    users.api.ts
    patients.api.ts
    prescriptions.api.ts
    admin.api.ts

  router/
    index.ts
    guards.ts

  stores/
    auth.store.ts
    prescription.store.ts

  layouts/
    AuthLayout.vue
    DashboardLayout.vue

  views/
    LoginView.vue

    doctor/
      DoctorPrescriptionsView.vue
      DoctorCreatePrescriptionView.vue
      DoctorPrescriptionDetailView.vue

    patient/
      PatientPrescriptionsView.vue
      PatientPrescriptionDetailView.vue

    admin/
      AdminDashboardView.vue
      AdminPrescriptionsView.vue

  components/
    ui/
      BaseButton.vue
      BaseInput.vue
      BaseSelect.vue
      BaseTable.vue
      BaseModal.vue
      BaseToast.vue

    prescriptions/
      PrescriptionForm.vue
      PrescriptionItemsForm.vue
      PrescriptionTable.vue
      PrescriptionStatusBadge.vue

    charts/
      PrescriptionsByStatusChart.vue
      PrescriptionsByDayChart.vue
      TopDoctorsChart.vue

  composables/
    useAuth.ts
    usePagination.ts
    useFilters.ts

  types/
    auth.ts
    user.ts
    prescription.ts


12. PDF de prescripción

Endpoint:

GET /api/prescriptions/{id}/pdf

Debe ser generado desde Laravel.

Librería recomendada:

- barryvdh/laravel-dompdf

El PDF debe contener:

- Código de prescripción.
- Fecha de creación.
- Estado.
- Datos del paciente.
- Datos del médico.
- Notas.
- Lista de ítems:
  - Nombre.
  - Dosis.
  - Cantidad.
  - Indicaciones.

Plus opcional:

- QR con el código de la prescripción.
- Firma del médico.
- Diseño institucional.


13. Semillas y credenciales de prueba

Seeder obligatorio:

Admin:

Email:
admin@test.com

Password:
admin123


Médico:

Email:
dr@test.com

Password:
dr123


Paciente:

Email:
patient@test.com

Password:
patient123


También debe crear:

- 5 a 10 prescripciones de ejemplo.
- Varias con estado pending.
- Varias con estado consumed.
- Mínimo 2 o 3 ítems por algunas prescripciones.


14. Variables de entorno

Backend .env

APP_NAME="Prescriptions API"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
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


Frontend .env

VITE_API_BASE_URL=http://localhost:8000/api


15. Scripts sugeridos

Backend

composer install

php artisan key:generate

php artisan migrate

php artisan db:seed

php artisan serve

php artisan test


Frontend

npm install

npm run dev

npm run build

npm run test


16. README requerido

El README debe incluir:

- Descripción del proyecto.
- Stack usado.
- Requisitos previos.
- Instalación del backend.
- Instalación del frontend.
- Variables de entorno.
- Comandos para migraciones.
- Comandos para seeders.
- Usuarios de prueba.
- Endpoints principales.
- Decisiones técnicas.
- Capturas opcionales.
- URLs de despliegue.
- Comandos de testing.


17. Documentación técnica

Debe explicar brevemente:

Autenticación:

- Uso de Laravel Sanctum.
- Manejo de token Bearer.
- Endpoint de perfil.
- Logout.

RBAC:

- Middleware de roles.
- Policies para recursos sensibles.
- Separación por admin, doctor y patient.

PDF:

- Generación desde backend.
- Librería usada.
- Datos incluidos.

Paginación:

- Uso de paginate().
- Filtros por query params.
- Ordenamiento por created_at DESC.

Frontend:

- Vue Router para navegación.
- Pinia para estado global.
- Axios para API.
- Guards por rol.


18. Entregables

1. Repositorio GitHub.

Puede ser monorepo:

prescriptions-app/
  backend/
  frontend/

O repos separados:

- prescriptions-backend.
- prescriptions-frontend.


2. Código fuente completo.

Debe incluir:

- Migraciones.
- Seeders.
- Tests básicos.
- README.
- Variables de entorno de ejemplo.


3. Backend desplegado.

Ejemplo:

https://prescriptions-api.up.railway.app


4. Frontend desplegado.

Ejemplo:

https://prescriptions-vue.vercel.app


5. Documentación.

Debe incluir:

- Decisiones técnicas.
- Endpoints.
- Instalación.
- Cuentas de prueba.


6. Testing.

Debe incluir:

- Comando para correr tests.
- Tests mínimos funcionales.


19. Criterios de evaluación

Funcionalidad: 35%

- Login.
- Roles.
- Flujos completos por rol.
- Creación de prescripciones.
- Consumo de prescripciones.
- PDF.
- Filtros.
- Paginación.
- Métricas.


Calidad de código: 25%

- Controladores limpios.
- Servicios separados.
- Form Requests.
- Resources.
- Validaciones.
- Manejo de errores.
- Consistencia en respuestas JSON.


Arquitectura: 20%

- Separación de responsabilidades.
- Middleware de roles.
- Policies.
- Relaciones Eloquent bien definidas.
- Migraciones claras.
- Índices en base de datos.
- Servicios para lógica compleja.


UX/UI: 15%

- Diseño responsive.
- Estados de carga.
- Estados de error.
- Estados vacíos.
- Toasts.
- Navegación clara.
- Protección de rutas.


Testing: 5%

- Pruebas básicas del backend.
- Prueba mínima del frontend.
- Cobertura de flujos críticos.


20. Plus opcionales para destacar

- Swagger / OpenAPI.
- Colección Postman o Insomnia.
- PDF con QR.
- Firma del médico.
- Auditoría con tabla audit_logs.
- Notificación por email al crear prescripción.
- Búsqueda avanzada por texto.
- Dark / light mode.
- Dashboard más completo.
- Exportación de métricas.
- Docker.
- CI/CD básico con GitHub Actions.


21. Checklist de aceptación

[ ] Login funciona correctamente.
[ ] El login devuelve token y perfil del usuario.
[ ] Las rutas están protegidas por autenticación.
[ ] Los roles funcionan correctamente.
[ ] El médico puede crear prescripciones.
[ ] La prescripción permite ítems manuales.
[ ] El médico sólo ve sus prescripciones.
[ ] El paciente sólo ve sus prescripciones.
[ ] El paciente puede marcar una prescripción como consumida.
[ ] El paciente puede descargar PDF.
[ ] El admin puede ver métricas.
[ ] Los listados tienen paginación.
[ ] Los listados tienen filtros.
[ ] Las migraciones corren sin errores.
[ ] Los seeders crean usuarios de prueba.
[ ] El README permite levantar el proyecto fácilmente.
[ ] El frontend está conectado correctamente con el backend.
[ ] El despliegue funciona.
[ ] Existen pruebas básicas.


22. Consideraciones finales

- No se exige catálogo de productos.
- Los ítems se escriben manualmente.
- La creación de usuarios desde admin es opcional.
- Se aceptan usuarios creados por seed.
- El alcance debe mantenerse como MVP.
- Los plus sólo deben implementarse si el core está completo.
- La prioridad es entregar una solución funcional, clara, segura y bien documentada.


23. Justificación del cambio de stack

La solución se implementará con Laravel API y Vue 3 como adaptación tecnológica del reto original.

Laravel API permite construir un backend sólido, seguro y mantenible, aprovechando herramientas nativas como Eloquent ORM, Migrations, Seeders, Form Requests, API Resources, Policies, Middlewares y Laravel Sanctum para autenticación.

Vue 3 permite construir una SPA moderna, modular y ligera, usando Vite, TypeScript, Vue Router, Pinia y TailwindCSS. Esta combinación facilita una experiencia de usuario fluida, una buena separación de responsabilidades y un despliegue independiente del backend.

La solución mantiene los mismos objetivos funcionales del reto:

- Autenticación.
- Roles.
- Prescripciones.
- PDF.
- Métricas.
- Filtros.
- Paginación.
- Dashboard administrativo.

El cambio de stack no reduce el alcance funcional, sino que adapta la implementación a tecnologías equivalentes y robustas.
