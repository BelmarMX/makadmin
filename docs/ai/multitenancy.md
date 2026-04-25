# Multitenancy y permisos

## Resolución de clínica

- El subdominio determina la clínica.
- Middleware `ResolveClinic`:
  - Lee `$request->getHost()`.
  - Extrae subdominio.
  - Busca `clinic_by_slug`.
  - Registra `app()->instance('current.clinic', $clinic)`.
- Si no existe, responder 404 genérico. No decir “clínica no encontrada” para evitar enumeración.
- `www` y apex son landing pública.
- El subdominio de superadmin sale de `SUPERADMIN_SUBDOMAIN`.

## Aislamiento

Todo modelo de dominio debe usar `BelongsToClinic`, que debe:

- Agregar `clinic_id` fillable.
- Definir relación `clinic()`.
- Registrar global scope `where clinic_id = current_clinic()->id`.
- En `creating`, setear `clinic_id = current_clinic()->id` automáticamente.

El superadmin puede usar `Clinic::withoutTenancy(fn() => ...)` solo si `auth()->user()->is_super_admin === true`; de lo contrario debe lanzar excepción.

## Migraciones

Toda tabla de dominio debe tener:

- `clinic_id` fk not null indexed.
- `deleted_at`.
- `created_at`.
- `updated_at`.

Agregar índices compuestos `(clinic_id, columna_frecuente)` en columnas consultadas con frecuencia.

## Spatie Permission con teams

- `teams = true` en `config/permission.php`.
- `team_id = clinic_id`.
- Roles/permisos se asignan dentro de la clínica activa.

## Roles base

Seeders mínimos:

- `super_admin` — global, fuera del team.
- `clinic_admin` — por clínica.
- `veterinarian` — por clínica.
- `groomer` — por clínica.
- `receptionist` — por clínica.
- `cashier` — por clínica.

## Permisos

- Cada módulo declara permisos en `app/Domain/{Modulo}/Permissions.php` como constantes.
- Base por recurso: `{modulo}.view`, `{modulo}.create`, `{modulo}.update`, `{modulo}.delete`.
- Permisos finos por módulo, por ejemplo:
  - `inventory.adjust_stock`
  - `pos.apply_discount`
  - `controlled_drugs.dispense`
- `ModulePermissionsSeeder` registra permisos de forma idempotente en cada deploy.

## Módulos activables

Tabla `clinic_modules`:

- `clinic_id`
- `module_key`
- `activated_at`
- `activated_by`

Middleware `EnsureModuleActive` en rutas del módulo. Si no está activo para la clínica, responder 403.

Solo superadmin activa/desactiva módulos.

## Test obligatorio por módulo

Cada módulo debe probar que clínica A no puede ver, editar ni borrar recursos de clínica B, incluso enviando el ID exacto en la URL.
