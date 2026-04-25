# Arquitectura del proyecto

## Estructura de carpetas

```txt
app/
├── Domain/                       # Un folder por módulo de negocio
│   ├── Clinic/                   # Gestión de clínicas (solo superadmin)
│   │   ├── Actions/
│   │   ├── Models/
│   │   ├── Policies/
│   │   ├── Events/
│   │   ├── DataTransferObjects/
│   │   └── Enums/
│   ├── Catalog/
│   │   ├── Geographic/
│   │   └── Veterinary/
│   ├── Patient/
│   ├── Inventory/
│   ├── Appointment/
│   ├── Pos/
│   ├── Grooming/
│   ├── Hospitalization/
│   ├── Supplier/
│   ├── Notification/
│   └── Reporting/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   ├── Clinic/
│   │   └── Portal/
│   ├── Middleware/
│   ├── Requests/
│   └── Resources/
├── Support/
├── Contracts/
│   └── Integrations/
├── Integrations/
│   ├── WhatsApp/Evolution/
│   ├── Calendar/Google/
│   └── Storage/Local/ | S3/
└── Providers/

resources/js/
├── pages/
│   ├── Admin/
│   ├── Clinic/
│   └── Portal/
├── components/
│   ├── ui/
│   └── domain/
├── composables/
├── layouts/
└── types/

.task/
docs/
├── brief.md
└── decisions/
```

No crear carpetas base nuevas sin justificación en la task.

## Convenciones de código

### Controladores

Máximo 10 líneas por método cuando sea razonable. Solo deben:

1. Recibir FormRequest o Request.
2. Ejecutar una Action.
3. Devolver Inertia, Resource, redirect o response.

### Actions

- Una clase = una operación de negocio.
- Método público único: `handle(...)`.
- Reciben DTOs o parámetros escalares, nunca FormRequests.
- Disparan eventos cuando corresponde.
- Son el lugar permitido para `DB::transaction`.

### FormRequests

- Uno por operación.
- `authorize()` siempre implementado.
- `rules()` con Rule objects cuando aplique.
- `prepareForValidation()` para normalizar input.

### Policies

- Una por modelo.
- Registradas en `AuthServiceProvider`.
- Asumen usuario autenticado y clínica resuelta.
- Validan permisos vía `$user->can('modulo.accion')`.

### Resources / DTOs

- API Resource para shape hacia Inertia/frontend.
- DTO readonly para comunicación interna entre capas backend.

### Modelos Eloquent

- Solo relaciones, casts, scopes y accessors/mutators.
- Si un método hace más que representar estado, debe moverse a una Action.

## Nombres

- Tablas: plural snake_case (`clinics`, `patient_records`).
- Modelos: singular PascalCase.
- Rutas: kebab-case (`/clinics/{clinic}/appointments`).
- Permisos: `dominio.accion` (`clinics.view`, `inventory.create`).
- Eventos: pasado indicativo (`ClinicCreated`, `DrugEgressRegistered`).

## Branding, dominio y subdominios

Toda referencia de marca/dominio debe consultarse desde `config/branding.php`.

Variables:

- `APP_BRAND_NAME`
- `APP_APEX_DOMAIN`
- `SUPERADMIN_SUBDOMAIN`

No hardcodear nombres como `vetfollow`, `admin` u otros valores de marca/dominio.

## Superadministrador

- Vive en `{SUPERADMIN_SUBDOMAIN}.{APP_APEX_DOMAIN}`.
- Tiene `is_super_admin = true` en `users`.
- Puede bypass de tenancy, pero siempre auditado.
- Capacidades:
  - CRUD de clínicas.
  - Activar/desactivar módulos por clínica.
  - Impersonar usuarios con log crítico en canal `security`.
  - Ver estadísticas globales y por clínica.
  - Gestionar roles/permisos de administradores de clínica.
- No debe editar datos de dominio de una clínica desde UI directa; para eso usa impersonation.

## Ambiente local

- `php artisan serve` + `npm run dev`, salvo cuando Laravel Herd ya sirva el proyecto.
- Subdominios locales vía Herd/Valet con wildcards.
- Reverb: `php artisan reverb:start`.
- Queue: `php artisan queue:work redis`.
- Seeders: 1 superadmin, 2 clínicas demo y usuarios por rol.
