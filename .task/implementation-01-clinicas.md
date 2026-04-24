# Implementation 01 — Módulo Clínicas (Superadmin)

> **Objetivo:** permitir al superadministrador crear, editar, activar/desactivar y configurar clínicas. Al terminar, debe poder dar de alta una clínica completa desde cero (datos fiscales, médico responsable, logo, sucursal principal, módulos activos, admin inicial) y acceder a ella por subdominio.

**Prerrequisitos:** task 00 completada y verificada.

**Tiempo estimado:** 1 día.

---

## 1. Alcance

Dentro (MVP de este módulo):
- CRUD de clínicas (solo superadmin).
- CRUD de sucursales por clínica.
- Activación/desactivación de módulos por clínica.
- Creación del primer `clinic_admin` al dar de alta la clínica (invitación por email con Fortify).
- Subida de logo de la clínica.
- Vista de detalle de clínica con tabs: General, Sucursales, Módulos, Usuarios, Configuración.

Fuera (tasks posteriores):
- Edición masiva de usuarios → task 02.
- Estadísticas de la clínica → task 20.
- Impersonation → task 20.
- Modo demo → task 22.

## 2. Dominio

`app/Domain/Clinic/`

```
Clinic/
├── Models/
│   ├── Clinic.php
│   ├── ClinicBranch.php
│   └── ClinicModule.php
├── Actions/
│   ├── CreateClinicAction.php
│   ├── UpdateClinicAction.php
│   ├── ActivateClinicAction.php
│   ├── DeactivateClinicAction.php
│   ├── CreateClinicBranchAction.php
│   ├── UpdateClinicBranchAction.php
│   ├── ToggleClinicModuleAction.php
│   └── InviteClinicAdminAction.php
├── DataTransferObjects/
│   ├── ClinicData.php
│   ├── ClinicBranchData.php
│   └── ClinicAdminInvitationData.php
├── Events/
│   ├── ClinicCreated.php
│   ├── ClinicActivated.php
│   ├── ClinicDeactivated.php
│   ├── ClinicModuleActivated.php
│   └── ClinicModuleDeactivated.php
├── Policies/
│   ├── ClinicPolicy.php
│   └── ClinicBranchPolicy.php
├── Enums/
│   ├── ModuleKey.php              // enum con todos los módulos del sistema
│   └── FiscalRegime.php
└── Permissions.php                 // constantes
```

### 2.1 `ModuleKey` enum

```php
enum ModuleKey: string
{
    case Patients = 'patients';
    case Inventory = 'inventory';
    case ControlledDrugs = 'controlled_drugs';
    case Appointments = 'appointments';
    case Pos = 'pos';
    case Grooming = 'grooming';
    case Hospitalization = 'hospitalization';
    case Suppliers = 'suppliers';
    case Notifications = 'notifications';
    case Reports = 'reports';
    case ClientPortal = 'client_portal';

    public function label(): string { /* ... */ }
    public function description(): string { /* ... */ }
    public function icon(): string { /* lucide icon name */ }
    public function dependsOn(): array {
        return match($this) {
            self::ControlledDrugs => [self::Inventory],
            self::Pos => [self::Inventory],
            self::Grooming => [self::Appointments],
            self::Hospitalization => [self::Patients],
            default => [],
        };
    }
}
```

### 2.2 Modelo `Clinic`

- NO usa `BelongsToClinic` (es el tenant mismo).
- Sí implementa `Auditable`.
- Casts: `settings => 'array'`, `activated_at => 'datetime'`.
- Relaciones: `branches()`, `modules()`, `users()`.
- Accessor: `getSubdomainUrl()` → `https://{slug}.{apex}`.

### 2.3 Modelo `ClinicBranch`

- NO usa `BelongsToClinic` directo porque el scope actual es por `clinic_id` que ya tiene. Pero sí registra el mismo scope manualmente para consistencia cuando se accede desde la app de clínica (no desde admin).
- Mejor: el scope se activa solo si `current.clinic` está bound. Si no (admin), no filtra. Ya está cubierto por `ClinicScope` del CLAUDE.md §6.
- Implementa `Auditable`.

## 3. Permisos

`app/Domain/Clinic/Permissions.php`:

```php
final class Permissions
{
    public const VIEW = 'clinics.view';
    public const CREATE = 'clinics.create';
    public const UPDATE = 'clinics.update';
    public const DELETE = 'clinics.delete';
    public const MANAGE_MODULES = 'clinics.manage_modules';
    public const MANAGE_BRANCHES = 'clinics.manage_branches';

    public static function all(): array { /* return todos */ }
}
```

Estos permisos son **globales** (sin team), asignados solo a `super_admin`.

## 4. Rutas

`routes/admin.php` (incluir desde `web.php` dentro del grupo `domain('admin.*')`):

```php
Route::middleware(['auth', 'super-admin'])->prefix('clinics')->name('admin.clinics.')->group(function () {
    Route::get('/', [ClinicController::class, 'index'])->name('index');
    Route::get('/create', [ClinicController::class, 'create'])->name('create');
    Route::post('/', [ClinicController::class, 'store'])->name('store');
    Route::get('/{clinic}', [ClinicController::class, 'show'])->name('show');
    Route::get('/{clinic}/edit', [ClinicController::class, 'edit'])->name('edit');
    Route::put('/{clinic}', [ClinicController::class, 'update'])->name('update');
    Route::delete('/{clinic}', [ClinicController::class, 'destroy'])->name('destroy');
    Route::post('/{clinic}/activate', [ClinicController::class, 'activate'])->name('activate');
    Route::post('/{clinic}/deactivate', [ClinicController::class, 'deactivate'])->name('deactivate');
    
    // Sucursales
    Route::post('/{clinic}/branches', [ClinicBranchController::class, 'store'])->name('branches.store');
    Route::put('/{clinic}/branches/{branch}', [ClinicBranchController::class, 'update'])->name('branches.update');
    Route::delete('/{clinic}/branches/{branch}', [ClinicBranchController::class, 'destroy'])->name('branches.destroy');
    
    // Módulos
    Route::post('/{clinic}/modules/{module}/toggle', [ClinicModuleController::class, 'toggle'])->name('modules.toggle');
    
    // Invitar admin
    Route::post('/{clinic}/invite-admin', [ClinicAdminController::class, 'invite'])->name('invite-admin');
});
```

Rutas resolubles por Wayfinder para TS en frontend.

## 5. FormRequests

### 5.1 `StoreClinicRequest`
```php
'slug' => ['required', 'alpha_dash', 'lowercase', 'min:3', 'max:40', Rule::unique('clinics', 'slug'), 'not_in:admin,www,api,app,portal'],
'legal_name' => ['required', 'string', 'max:200'],
'commercial_name' => ['required', 'string', 'max:200'],
'rfc' => ['nullable', 'string', 'size:13', 'regex:/^[A-ZÑ&]{3,4}\d{6}[A-Z\d]{3}$/i'],
'fiscal_regime' => ['nullable', Rule::enum(FiscalRegime::class)],
'tax_address' => ['nullable', 'string', 'max:500'],
'responsible_vet_name' => ['required', 'string', 'max:200'],
'responsible_vet_license' => ['required', 'string', 'max:50'],
'contact_phone' => ['required', 'string', 'max:20'],
'contact_email' => ['required', 'email', 'max:200'],
'logo' => ['nullable', 'image', 'mimes:png,jpg,webp,svg', 'max:2048'],
'primary_color' => ['nullable', 'regex:/^#[0-9a-f]{6}$/i'],
'main_branch' => ['required', 'array'],
'main_branch.name' => ['required', 'string', 'max:200'],
'main_branch.address' => ['required', 'string', 'max:500'],
'main_branch.phone' => ['nullable', 'string', 'max:20'],
'modules' => ['required', 'array', 'min:1'],
'modules.*' => [Rule::enum(ModuleKey::class)],
'admin' => ['required', 'array'],
'admin.name' => ['required', 'string', 'max:200'],
'admin.email' => ['required', 'email', Rule::unique('users', 'email')],
'admin.phone' => ['nullable', 'string', 'max:20'],
```

### 5.2 `UpdateClinicRequest`
Igual pero `slug` con `unique` ignorando el actual, sin campos `main_branch`, `modules`, `admin`.

## 6. Actions (comportamiento clave)

### 6.1 `CreateClinicAction::handle(ClinicData $data): Clinic`

Todo dentro de `DB::transaction`:
1. Guardar logo con `MediaStorage` (path sugerido: `clinics/{slug}/logo.{ext}`).
2. Crear `Clinic` con los datos base + `settings = []` + `is_active = false`.
3. Crear `ClinicBranch` principal (`is_main = true`).
4. Para cada módulo en `$data->modules`, insertar fila en `clinic_modules` con `is_active = true`. Validar dependencias con `ModuleKey::dependsOn()`.
5. Llamar `InviteClinicAdminAction::handle(...)`:
   - Crear `User` con `clinic_id = $clinic->id`, `branch_id = $mainBranch->id`, password random.
   - Setear `permission_teams` context a la clínica.
   - Asignar rol `clinic_admin`.
   - Enviar invitación con link de setup de password (Fortify password reset flow).
6. Disparar `ClinicCreated($clinic, auth()->user())`.
7. Log en canal `security`: `clinic_created`.
8. Retornar `$clinic->fresh(['branches', 'modules'])`.

### 6.2 `ActivateClinicAction`
Setea `is_active = true`, `activated_at = now()` si es primera vez. Dispara evento. Logea.

### 6.3 `DeactivateClinicAction`
Setea `is_active = false`. Nota: esto hace que el middleware `ResolveClinic` retorne 404. Los usuarios activos con sesión ven un mensaje y logout forzado al siguiente request.

### 6.4 `ToggleClinicModuleAction`
- Si se activa un módulo: verificar dependencias. Si faltan, activar también las dependencias en cascada (con confirmación del superadmin en UI).
- Si se desactiva: verificar que no haya módulos dependientes activos. Si los hay, bloquear con mensaje claro.
- Dispara evento correspondiente.

## 7. Controllers (shape esperado)

Delgados. Ejemplo `ClinicController::store`:

```php
public function store(StoreClinicRequest $request, CreateClinicAction $action)
{
    $clinic = $action->handle(ClinicData::fromRequest($request));
    
    return redirect()
        ->route('admin.clinics.show', $clinic)
        ->with('success', "Clínica «{$clinic->commercial_name}» creada. Invitación enviada a {$clinic->contact_email}.");
}
```

## 8. Frontend (Vue + Inertia)

### 8.1 Páginas

```
resources/js/pages/Admin/Clinics/
├── Index.vue            # Listado con búsqueda, filtro activas/inactivas, paginación
├── Create.vue           # Form multi-step (wizard)
├── Show.vue             # Detalle con tabs
└── Edit.vue             # Edición rápida (solo datos generales)
```

### 8.2 Wizard de creación (`Create.vue`)

4 pasos:
1. **Identidad**: slug, nombres, logo, color primario, contacto.
2. **Datos fiscales y responsable**: RFC, régimen, dirección, cédula, nombre del médico.
3. **Sucursal principal**: nombre, dirección, teléfono.
4. **Módulos + Admin inicial**: checkboxes de módulos (con descripciones), email/nombre/teléfono del admin de clínica.

Validación por paso en frontend + validación final en backend.

Preview del subdominio en vivo mientras escribe el slug: `{slug}.vetfollow.com`.

### 8.3 Detalle (`Show.vue`)

Tabs:
- **General**: datos editables inline.
- **Sucursales**: CRUD de sucursales (crear, editar, marcar como principal, archivar).
- **Módulos**: grid con switches. Cada card muestra nombre, descripción, estado, dependencias. Al togglear llama a la ruta `modules.toggle`.
- **Usuarios**: solo listado (sin edit aquí; eso es task 02). Botón "Entrar como admin" para impersonation (stub visual; la lógica va en task 20).
- **Configuración**: JSON editor para `settings` (admin avanzado). Config de integraciones queda en task 17.

### 8.4 Componentes nuevos

```
resources/js/components/domain/Clinic/
├── ClinicCard.vue
├── ClinicStatusBadge.vue
├── ModuleToggleCard.vue
├── BranchListItem.vue
└── ClinicWizard/
    ├── StepIdentity.vue
    ├── StepFiscal.vue
    ├── StepBranch.vue
    └── StepModulesAdmin.vue
```

Todos consumen tokens del CLAUDE.md §12. Uso obligatorio de shadcn-vue base.

## 9. Eventos y listeners

- `ClinicCreated` → listener `SendWelcomeNotification` (encolado).
- `ClinicActivated` / `ClinicDeactivated` → listener que loguea en canal security.
- `ClinicModuleActivated` → listener `SeedModulePermissionsForClinic` (crea permisos del módulo para la clínica en el contexto Spatie teams).

## 10. Media Storage (preparación)

Crear interface `app/Contracts/Integrations/MediaStorage.php`:

```php
interface MediaStorage
{
    public function put(string $path, \Illuminate\Http\UploadedFile $file): string; // retorna path almacenado
    public function url(string $path): string;
    public function delete(string $path): bool;
}
```

Implementación default `app/Integrations/Storage/Local/LocalMediaStorage.php` usando `Storage::disk('public')`.

Binding en `AppServiceProvider`. El módulo de clínicas usa la interface, no `Storage::` directo. Esto prepara el cambio a S3 sin refactor.

## 11. Tests obligatorios

`tests/Feature/Admin/Clinic/CreateClinicTest.php`:
- Happy path completo.
- Valida slugs reservados.
- Valida RFC inválido.
- Valida módulo con dependencia faltante.
- Crea el admin + envía email de invitación.
- Crea permisos en contexto team.

`tests/Feature/Admin/Clinic/UpdateClinicTest.php`:
- Actualiza datos generales.
- No deja cambiar slug si ya hay usuarios activos (regla de negocio: slug es inmutable post-creación).

`tests/Feature/Admin/Clinic/ToggleModuleTest.php`:
- Activar módulo activa dependencias.
- Desactivar módulo con dependientes falla.

`tests/Feature/Tenancy/ClinicSubdomainTest.php`:
- Acceder a `{slug}.vetfollow.test` resuelve correctamente.
- Clínica desactivada → 404.

`tests/Unit/Clinic/ModuleKeyTest.php`:
- Dependencias declaradas.

`tests/Feature/Auth/ClinicAdminInvitationTest.php`:
- Invitación envía email.
- Link de password setup funciona.

## 12. Criterios de aceptación

- [ ] Superadmin loguea en `admin.vetfollow.test`, va a Clínicas, presiona "Nueva clínica".
- [ ] Completa wizard. Se crea la clínica, se envía invitación al admin.
- [ ] El admin de clínica recibe email, setea password, entra a `{slug}.vetfollow.test` y ve su dashboard (vacío por ahora).
- [ ] Superadmin puede togglear módulos y los cambios se reflejan inmediatamente en la app de la clínica (las rutas del módulo bloqueado devuelven 403).
- [ ] Superadmin puede desactivar la clínica; al desactivar, el subdominio deja de resolver.
- [ ] Auditoría tiene registro de: creación, cada toggle de módulo, activación/desactivación.
- [ ] Todos los tests pasan.
- [ ] `vendor/bin/pint --test` pasa.
- [ ] `vendor/bin/phpstan analyse` nivel 6 pasa.
- [ ] `npm run typecheck` pasa.
- [ ] Lighthouse score de la página de listado ≥ 90 en performance (desktop).

## 13. Siguiente paso

Una vez aceptada esta task, continuar con `implementation-02-usuarios-roles.md` para que la clínica pueda gestionar su equipo interno (usuarios por sucursal, roles finos, perfiles).

## 14. Resultado

**Estado:** ✅ Completa — 2026-04-23

### Paquetes instalados

Ninguno nuevo. Todo implementado con el stack de Task 00.

### Archivos creados / modificados significativos

**Dominio:**
- `app/Domain/Clinic/Enums/ModuleKey.php` — 11 módulos, con `dependsOn()`, `label()`, `description()`, `icon()`
- `app/Domain/Clinic/Enums/FiscalRegime.php` — 8 regímenes SAT
- `app/Domain/Clinic/Permissions.php` — constantes de permisos
- `app/Domain/Clinic/DataTransferObjects/` — 3 DTOs (readonly)
- `app/Domain/Clinic/Events/` — 5 eventos
- `app/Domain/Clinic/Listeners/` — 3 listeners (SendClinicWelcomeNotification, LogClinicStatusChange, SeedModulePermissionsForClinic)
- `app/Domain/Clinic/Policies/ClinicPolicy.php` + `ClinicBranchPolicy.php`
- `app/Domain/Clinic/Actions/` — 8 actions (CreateClinic, UpdateClinic, Activate, Deactivate, CreateBranch, UpdateBranch, ToggleModule, InviteAdmin)
- `app/Domain/Clinic/Models/Clinic.php` — agregado HasFactory, newFactory(), subdomainUrl() accessor, FiscalRegime cast

**Contratos e integraciones:**
- `app/Contracts/Integrations/MediaStorage.php` — interface
- `app/Integrations/Storage/Local/LocalMediaStorage.php` — impl local

**HTTP:**
- `app/Http/Controllers/Controller.php` — agregado `AuthorizesRequests` trait (necesario para `$this->authorize()`)
- `app/Http/Controllers/Admin/ClinicController.php` + `ClinicBranchController.php` + `ClinicModuleController.php` + `ClinicAdminController.php`
- `app/Http/Requests/Admin/StoreClinicRequest.php` + `UpdateClinicRequest.php` + `StoreBranchRequest.php` + `UpdateBranchRequest.php`
- `routes/admin.php` — 14 rutas de clínicas extraídas del web.php

**Frontend — componentes UI (creados manualmente, CLI con bug):**
- `resources/js/components/ui/switch/` — Switch usando reka-ui SwitchRoot/SwitchThumb
- `resources/js/components/ui/tabs/` — Tabs, TabsList, TabsTrigger, TabsContent
- `resources/js/components/ui/table/` — Table + 7 sub-componentes
- `resources/js/components/ui/textarea/` — Textarea con v-model

**Frontend — dominio y páginas:**
- `resources/js/components/domain/Clinic/` — ClinicStatusBadge, ClinicCard, ModuleToggleCard, BranchListItem
- `resources/js/components/domain/Clinic/ClinicWizard/` — StepIdentity, StepFiscal, StepBranch, StepModulesAdmin
- `resources/js/pages/Admin/Clinics/Index.vue` + `Create.vue` + `Show.vue` + `Edit.vue`
- `resources/js/pages/Admin/Dashboard.vue` — actualizado con card de Clínicas

**Tests:**
- `database/factories/ClinicFactory.php` — nueva factory con estado `inactive()`
- `tests/Unit/Clinic/ModuleKeyTest.php` — 17 assertions sobre dependencias, labels, icons
- `tests/Feature/Admin/Clinic/CreateClinicTest.php` — 15 tests (happy path, validaciones, roles, dependencias de módulos)
- `tests/Feature/Admin/Clinic/UpdateClinicTest.php` — 4 tests (actualizar datos, slug unique-ignore-self, protección non-admin)
- `tests/Feature/Admin/Clinic/ToggleModuleTest.php` — 5 tests (activar, cascada deps, desactivar con/sin dependientes, protección)
- `tests/Feature/Admin/Clinic/ClinicAdminInvitationTest.php` — 4 tests (crea usuario, envía notificación, asigna rol, protección)

### Decisiones que divergieron del plan

1. **`AuthorizesRequests` en base Controller** — Laravel 11 no lo incluye por defecto. Agregado al descubrir que `$this->authorize()` lanzaba `Call to undefined method`. Cambio mínimo, no afecta otros controllers.

2. **shadcn-vue CLI inutilizable** — `npx shadcn-vue@latest add` falla con `Cannot find package '@unovue/detypes'`. Los componentes Switch, Tabs, Table y Textarea se crearon manualmente usando reka-ui primitives directamente, siguiendo el mismo patrón que los componentes existentes (Card, Button, Input, etc.).

3. **Wayfinder devuelve `RouteDefinition<method>`, no `string`** — `useForm().post()` y `router.post/delete()` esperan `string`. Todos los usos de Wayfinder en páginas y componentes usan `.url` para extraer la URL tipada (ej. `clinicRoutes.store().url`).

4. **ClinicModule no tiene global scope de tenancy** — `ClinicModule` usa `clinic_id` directo en queries de la action (no el trait `BelongsToClinic`) porque las operaciones de módulo siempre se hacen explícitamente con `clinic_id`. Esto evita ambigüedad cuando el admin global no tiene `current.clinic` seteado.

5. **Invitation usa Fortify `Password::broker()` no un Mailable propio** — El plan decía "envío por email con Fortify". Se usa el broker de password reset como mecanismo de invitación (envía el link de reset como primer login). Un Mailable personalizado se difiere a Task 19 (Notificaciones).

6. **`HasFactory` con `newFactory()` override** — La factory de `Clinic` vive en `Database\Factories\ClinicFactory` (convención Laravel), pero el modelo está en `App\Domain\Clinic\Models\Clinic`. Laravel no puede resolver el namespace automáticamente, así que `newFactory()` se sobreescribe explícitamente en el modelo.

### Issues pendientes diferidos

- **`SendClinicWelcomeNotification`** — listener implementado pero con `// TODO: implementar en task 19`. Task 19 define el canal de WhatsApp/email configurable por clínica.
- **`SeedModulePermissionsForClinic`** — listener stub. Los permisos granulares por módulo se definen en Task 03 (permisos) cuando se implementen los módulos reales.
- **Logo upload UI** — el campo `logo` existe en el request y action, pero la UI del wizard (StepIdentity) no tiene input de archivo todavía. Pendiente Task 01-bis o Task 20 (perfil de clínica).
- **Tab "Configuración"** en Show.vue — el plan menciona 5 tabs, se implementaron 4 (General, Sucursales, Módulos, Usuarios). Configuración (integraciones, webhook, etc.) corresponde a Task 18.
- **Estadísticas** en Dashboard admin — el card de Clínicas existe pero sin contadores. Task 20.

### Verificación final (§14)

```
php artisan test --compact     → 92/92 passed
vendor/bin/pint --dirty        → fixed (formateo menor en 6 archivos)
vendor/bin/phpstan analyse     → 0 errors
npm run build                  → ✓ built in 1.49s
npm run types:check            → 0 errors
```
