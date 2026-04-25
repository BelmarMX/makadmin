# Implementation 03 — Usuarios y Roles

> **Objetivo:** permitir al administrador de cada clínica gestionar su equipo (usuarios por sucursal, roles, permisos finos) sin que un usuario de otra clínica vea ni pueda interferir. Al terminar, cualquier clínica puede tener veterinarios, esteticistas, recepcionistas con permisos independientes y editables.

**Prerrequisitos:** tasks 00, 01 y 02 completadas.

**Tiempo estimado:** 0.5 día.

**Referencia:** `CLAUDE.md` §7 (permisos y roles), §6 (multitenancy).

---

## 1. Alcance

Dentro:
- CRUD de usuarios por clínica + sucursal.
- Asignación de roles base (veterinarian, groomer, receptionist, cashier) por usuario.
- Asignación granular de permisos (ver, crear, editar, eliminar por módulo).
- Perfil editable del usuario (avatar, nombre, teléfono, contraseña).
- Auditoría de cambios de permisos/roles.
- Desactivación de usuarios sin borrado físico.
- Envío de invitación por email a nuevos usuarios con link de setup.
- Dashboard de usuarios filtrable (activos, inactivos, por sucursal, por rol).

Fuera:
- Sincronización con LDAP/Active Directory.
- SSO (OAuth2, SAML).
- 2FA avanzado (ya existe Fortify, solo custom UI).
- Auditoría detallada de login (eso es task 20 seguridad avanzada).

---

## 2. Dominio

`app/Domain/User/`

```
User/
├── Models/
│   └── User.php                     # Model central, ya existe en Laravel
├── Actions/
│   ├── CreateUserAction.php
│   ├── UpdateUserAction.php
│   ├── DeactivateUserAction.php
│   ├── AssignRoleAction.php
│   ├── RevokeRoleAction.php
│   ├── SyncPermissionsAction.php
│   └── SendInvitationEmailAction.php
├── DataTransferObjects/
│   ├── UserData.php
│   ├── RoleAssignmentData.php
│   └── PermissionAssignmentData.php
├── Policies/
│   └── UserPolicy.php
├── Events/
│   ├── UserCreated.php
│   ├── UserUpdated.php
│   ├── UserDeactivated.php
│   ├── RoleAssigned.php
│   └── PermissionsChanged.php
├── Enums/
│   └── UserStatus.php
└── Permissions.php
```

---

## 3. Modelo User (actualización)

El modelo `User` ya existe en Laravel. Ampliar con:

```php
namespace App\Models;

use App\Support\Tenancy\BelongsToClinic;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use BelongsToClinic;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use HasRoles;
    use AuditableTrait;

    protected $fillable = [
        'clinic_id',
        'branch_id',
        'name',
        'email',
        'phone',
        'password',
        'avatar_path',
        'professional_license',  // cédula
        'is_super_admin',
        'is_active',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_super_admin' => 'boolean',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    // Relaciones
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(ClinicBranch::class);
    }

    // Accessors
    public function getInitialsAttribute(): string
    {
        return strtoupper(implode('', array_map(fn($word) => $word[0] ?? '', explode(' ', $this->name))));
    }

    // Scopes
    public function scopeActive(): Builder
    {
        return $this->where('is_active', true);
    }

    public function scopeInactive(): Builder
    {
        return $this->where('is_active', false);
    }

    public function scopeByBranch($query, ClinicBranch $branch)
    {
        return $query->where('branch_id', $branch->id);
    }

    public function scopeByRole($query, string $role)
    {
        return $query->whereHasRole($role);
    }

    // Helpers
    public function isSuperAdmin(): bool
    {
        return $this->is_super_admin === true;
    }

    public function isClinicAdmin(): bool
    {
        return $this->hasRole('clinic_admin');
    }

    public function isVeterinarian(): bool
    {
        return $this->hasRole('veterinarian');
    }

    public function isGroomer(): bool
    {
        return $this->hasRole('groomer');
    }

    public function isReceptionist(): bool
    {
        return $this->hasRole('receptionist');
    }

    public function isCashier(): bool
    {
        return $this->hasRole('cashier');
    }
}
```

---

## 4. Permisos

`app/Domain/User/Permissions.php`:

```php
<?php

namespace App\Domain\User;

final class Permissions
{
    public const VIEW = 'users.view';
    public const CREATE = 'users.create';
    public const UPDATE = 'users.update';
    public const DEACTIVATE = 'users.deactivate';
    public const RESTORE = 'users.restore';
    public const MANAGE_ROLES = 'users.manage_roles';
    public const MANAGE_PERMISSIONS = 'users.manage_permissions';
    public const VIEW_PROFILE = 'users.view_profile';

    public static function all(): array
    {
        return [
            self::VIEW,
            self::CREATE,
            self::UPDATE,
            self::DEACTIVATE,
            self::RESTORE,
            self::MANAGE_ROLES,
            self::MANAGE_PERMISSIONS,
            self::VIEW_PROFILE,
        ];
    }
}
```

Se asignan en el contexto de la clínica (teams, Spatie Permission).

---

## 5. Migraciones

### 5.1 Actualizar tabla `users` (ya existe, agregar columnas faltantes)

Si en task 00 no agregaste todas estas columnas, hacerlo ahora:

```php
Schema::table('users', function (Blueprint $table) {
    // Si ya existen, saltar
    if (!Schema::hasColumn('users', 'clinic_id')) {
        $table->foreignId('clinic_id')->nullable()->after('id')->constrained()->nullOnDelete();
    }
    if (!Schema::hasColumn('users', 'branch_id')) {
        $table->foreignId('branch_id')->nullable()->after('clinic_id')->constrained('clinic_branches')->nullOnDelete();
    }
    if (!Schema::hasColumn('users', 'is_super_admin')) {
        $table->boolean('is_super_admin')->default(false)->after('branch_id');
    }
    if (!Schema::hasColumn('users', 'phone')) {
        $table->string('phone')->nullable()->after('email');
    }
    if (!Schema::hasColumn('users', 'avatar_path')) {
        $table->string('avatar_path')->nullable()->after('phone');
    }
    if (!Schema::hasColumn('users', 'professional_license')) {
        $table->string('professional_license')->nullable()->after('avatar_path');
    }
    if (!Schema::hasColumn('users', 'is_active')) {
        $table->boolean('is_active')->default(true)->after('professional_license');
    }
    if (!Schema::hasColumn('users', 'last_login_at')) {
        $table->timestamp('last_login_at')->nullable()->after('is_active');
    }
    if (!Schema::hasColumn('users', 'last_login_ip')) {
        $table->string('last_login_ip')->nullable()->after('last_login_at');
    }
    if (!Schema::hasColumn('users', 'deleted_at')) {
        $table->softDeletes()->after('last_login_ip');
    }
});
```

---

## 6. FormRequests

### 6.1 `StoreUserRequest`

```php
'name' => ['required', 'string', 'max:200'],
'email' => ['required', 'email', 'unique:users,email'],
'phone' => ['nullable', 'string', 'max:20'],
'branch_id' => ['required', 'exists:clinic_branches,id'],
'professional_license' => ['nullable', 'string', 'max:50'],
'password' => ['required', 'string', 'min:10', 'confirmed'],
'avatar' => ['nullable', 'image', 'mimes:png,jpg,webp', 'max:2048'],
'roles' => ['required', 'array', 'min:1'],
'roles.*' => [Rule::enum(RoleEnum::class)],
```

Método `authorize()`:
```php
public function authorize()
{
    return $this->user()->can('users.create');
}
```

### 6.2 `UpdateUserRequest`

Igual pero sin password (opcional) y email con `unique:users,email,{$user->id}`.

### 6.3 `UpdateProfileRequest`

Solo el usuario para actualizar su propio perfil:
```php
'name' => ['required', 'string', 'max:200'],
'phone' => ['nullable', 'string', 'max:20'],
'avatar' => ['nullable', 'image', 'mimes:png,jpg,webp', 'max:2048'],
'password' => ['nullable', 'string', 'min:10', 'confirmed'],
```

---

## 7. Actions (comportamiento clave)

### 7.1 `CreateUserAction::handle(UserData $data, ClinicBranch $branch): User`

Dentro de `DB::transaction`:
1. Subir avatar si existe, usando `MediaStorage` (path: `users/{clinic_id}/{user_id}/avatar.{ext}`).
2. Crear `User` con `clinic_id = current_clinic`, `branch_id = $branch->id`, `is_active = true`.
3. Para cada rol en `$data->roles`, asignar vía Spatie (en contexto de la clínica).
4. Llamar `SendInvitationEmailAction::handle($user)` (encolado).
5. Disparar `UserCreated($user, auth()->user())`.
6. Log en canal `security`: `user_created`.
7. Retornar `$user`.

### 7.2 `SendInvitationEmailAction::handle(User $user): void`

Encolado (Job):
1. Generar token temporal de Fortify (password reset).
2. Enviar email con link `{clinic_url}/auth/set-password?token={token}`.
3. Email debe incluir: nombre del usuario, nombre de la clínica, link de setup.

### 7.3 `AssignRoleAction::handle(User $user, string $role): void`

1. Validar que el rol existe en Spatie y está en contexto de la clínica.
2. `$user->assignRole($role)` (Spatie en contexto team = clinic).
3. Disparar `RoleAssigned($user, $role, auth()->user())`.
4. Disparar `PermissionsChanged($user)` para que frontend refresque permisos.

### 7.4 `SyncPermissionsAction::handle(User $user, array $permissionsByModule): void`

Recibe estructura:
```php
[
    'inventory' => ['view', 'create', 'update'],
    'pos' => ['view', 'create'],
    'patients' => ['view'],
    // ... por cada módulo activo en la clínica
]
```

Lógica:
1. Iterar módulos activos de la clínica.
2. Para cada módulo, revisar permisos solicitados.
3. Revocar permisos actuales no en la lista.
4. Asignar nuevos permisos.
5. Disparar `PermissionsChanged($user)`.

---

## 8. Controllers (shape esperado)

### 8.1 `UserController` (admin de clínica)

```php
public function index(Request $request)
{
    $query = User::query()
        ->where('clinic_id', current_clinic()->id)
        ->with(['branch:id,name', 'roles:id,name']);

    if ($request->has('branch_id')) {
        $query->where('branch_id', $request->integer('branch_id'));
    }
    if ($request->has('role')) {
        $query->whereHasRole($request->string('role'));
    }
    if ($request->has('status')) {
        $status = $request->string('status');
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }
    }
    if ($request->has('q')) {
        $q = $request->string('q');
        $query->where(function ($subquery) use ($q) {
            $subquery->where('name', 'ilike', "%{$q}%")
                     ->orWhere('email', 'ilike', "%{$q}%");
        });
    }

    return Inertia::render('Clinic/Users/Index', [
        'users' => $query->paginate(15),
        'branches' => current_clinic()->branches()->active()->get(),
        'roles' => RoleEnum::cases(),
    ]);
}

public function create()
{
    return Inertia::render('Clinic/Users/Create', [
        'branches' => current_clinic()->branches()->active()->get(),
        'roles' => RoleEnum::cases(),
        'modules' => current_clinic()->modules()->where('is_active', true)->get(),
    ]);
}

public function store(StoreUserRequest $request, CreateUserAction $action)
{
    $branch = ClinicBranch::where('clinic_id', current_clinic()->id)
        ->findOrFail($request->integer('branch_id'));

    $user = $action->handle(UserData::fromRequest($request), $branch);

    return redirect()
        ->route('clinic.users.show', $user)
        ->with('success', "Usuario {$user->name} creado. Invitación enviada a {$user->email}");
}

public function show(User $user)
{
    $this->authorize('view', $user);

    return Inertia::render('Clinic/Users/Show', [
        'user' => $user->load('branch', 'roles'),
        'permissions' => $this->userPermissionsByModule($user),
        'modules' => current_clinic()->modules()->where('is_active', true)->get(),
    ]);
}

public function edit(User $user)
{
    $this->authorize('update', $user);

    return Inertia::render('Clinic/Users/Edit', [
        'user' => $user,
        'branches' => current_clinic()->branches()->active()->get(),
        'roles' => RoleEnum::cases(),
    ]);
}

public function update(UpdateUserRequest $request, User $user, UpdateUserAction $action)
{
    $this->authorize('update', $user);

    $action->handle($user, UserData::fromRequest($request));

    return redirect()->route('clinic.users.show', $user)
        ->with('success', 'Usuario actualizado');
}

public function deactivate(User $user)
{
    $this->authorize('deactivate', $user);

    $user->update(['is_active' => false]);
    UserDeactivated::dispatch($user, auth()->user());

    return redirect()->back()->with('success', 'Usuario desactivado');
}

public function restore(User $user)
{
    $this->authorize('restore', $user);

    $user->update(['is_active' => true]);

    return redirect()->back()->with('success', 'Usuario activado');
}

private function userPermissionsByModule(User $user): array
{
    $result = [];
    foreach (current_clinic()->modules()->where('is_active', true)->get() as $module) {
        $moduleKey = $module->module_key;
        $result[$moduleKey] = $user->getPermissionsViaRoles()
            ->filter(fn($perm) => str_starts_with($perm->name, $moduleKey . '.'))
            ->pluck('name')
            ->toArray();
    }
    return $result;
}
```

### 8.2 `ProfileController` (usuario edita su perfil)

```php
public function edit()
{
    return Inertia::render('Clinic/Profile/Edit', [
        'user' => auth()->user(),
    ]);
}

public function update(UpdateProfileRequest $request)
{
    $user = auth()->user();

    if ($request->hasFile('avatar')) {
        $path = Storage::disk('public')->putFile(
            "users/{$user->clinic_id}/{$user->id}",
            $request->file('avatar')
        );
        $user->update(['avatar_path' => $path]);
    }

    $user->update($request->only('name', 'phone'));

    if ($request->filled('password')) {
        $user->update(['password' => Hash::make($request->string('password'))]);
    }

    return redirect()->back()->with('success', 'Perfil actualizado');
}
```

---

## 9. Rutas

```php
Route::middleware(['auth', 'tenant'])->prefix('users')->name('clinic.users.')->group(function () {
    Route::get('/', [UserController::class, 'index'])->middleware('permission:users.view')->name('index');
    Route::get('/create', [UserController::class, 'create'])->middleware('permission:users.create')->name('create');
    Route::post('/', [UserController::class, 'store'])->middleware('permission:users.create')->name('store');
    Route::get('/{user}', [UserController::class, 'show'])->middleware('permission:users.view')->name('show');
    Route::get('/{user}/edit', [UserController::class, 'edit'])->middleware('permission:users.update')->name('edit');
    Route::put('/{user}', [UserController::class, 'update'])->middleware('permission:users.update')->name('update');
    Route::post('/{user}/deactivate', [UserController::class, 'deactivate'])->middleware('permission:users.deactivate')->name('deactivate');
    Route::post('/{user}/restore', [UserController::class, 'restore'])->middleware('permission:users.restore')->name('restore');
});

Route::middleware(['auth', 'tenant'])->prefix('profile')->name('clinic.profile.')->group(function () {
    Route::get('/', [ProfileController::class, 'edit'])->name('edit');
    Route::put('/', [ProfileController::class, 'update'])->name('update');
});
```

---

## 10. Frontend (Vue)

```
resources/js/pages/Clinic/Users/
├── Index.vue            # Listado con filtros (sucursal, rol, estado, búsqueda)
├── Create.vue           # Formulario de creación + asignación de roles
├── Show.vue             # Detalle: datos, roles, permisos por módulo, historial
└── Edit.vue             # Edición de datos básicos

resources/js/pages/Clinic/Profile/
└── Edit.vue             # Edición de perfil propio (nombre, teléfono, avatar, contraseña)

resources/js/components/domain/User/
├── UserCard.vue
├── UserStatusBadge.vue
├── RoleAssignmentForm.vue
└── PermissionGrid.vue   # Grid de módulos x permisos (ver, crear, editar, eliminar)
```

### 10.1 Ejemplo: `UserCard.vue`

Muestra avatar (fallback a initials), nombre, email, rol principal, estado (activo/inactivo), sucursal.

### 10.2 Ejemplo: `PermissionGrid.vue`

Grid interactivo:
- Filas: módulos activos en la clínica
- Columnas: ver, crear, editar, eliminar
- Checkboxes que disparan `PATCH /users/{id}/permissions`

---

## 11. Events y Listeners

- `UserCreated` → listener `SendWelcomeEmail` (encolado)
- `RoleAssigned` → listener `NotifyRoleChange` (encolado)
- `PermissionsChanged` → **no listener**, solo evento para que frontend escuche y refresque permisos en tiempo real vía WebSocket (si existe canal de notificaciones)

---

## 12. Seeders

### 12.1 `UserSeeder`

En dev, crea:
- 1 superadmin con `is_super_admin = true` (credentials en .env o prompt).
- Por cada clínica demo:
  - 1 `clinic_admin` (admin)
  - 1 `veterinarian`
  - 1 `groomer`
  - 1 `receptionist`
  - 1 `cashier`

Todos con email predecible: `{role}@{clinic.slug}.test`.

### 12.2 `RolesAndPermissionsSeeder`

Crea roles base (clinic_admin, veterinarian, groomer, receptionist, cashier).

Por cada módulo activo, registra permisos en `ModulePermissionsSeeder` (task 02 ya lo hizo, esto es referencia).

---

## 13. Tests obligatorios

`tests/Feature/User/`:

- `CreateUserTest.php` — Happy path, validaciones, invitación enviada.
- `UpdateUserTest.php` — Cambios de datos, avatar upload.
- `UserTenancyTest.php` — Usuario de clínica A no puede ver/editar usuarios de clínica B.
- `RoleAssignmentTest.php` — Asignación de roles, validación de permiso.
- `PermissionSyncTest.php` — Sincronización de permisos por módulo, revocación correcta.
- `ProfileUpdateTest.php` — Usuario actualiza su propio perfil, no el de otros.
- `UserDeactivationTest.php` — Soft delete, restauración, sesiones activas se cierra (tarea futura si requiere).

`tests/Unit/User/`:

- `UserModelTest.php` — Scopes (active, inactive, byBranch, byRole), helpers (isVeterinarian, etc.).

---

## 14. Criterios de aceptación

- [ ] `php artisan migrate:fresh --seed` crea superadmin + usuarios demo por clínica.
- [ ] Admin de clínica accede a `/users` y ve listado de su equipo (no ve otros).
- [ ] Crear nuevo usuario: rellena form, recibe invitación por email con link de setup.
- [ ] Nuevo usuario recibe email, hace click en link, setea contraseña, entra.
- [ ] Admin asigna roles: checkboxes por módulo (ver, crear, editar, eliminar) reflejan permisos correctos.
- [ ] Usuario de clínica A **no puede** acceder a `/clinic-b/users` (403).
- [ ] Desactivar usuario: soft delete, ya no puede entrar, puede restaurarse.
- [ ] Perfil propio editable (nombre, teléfono, avatar, contraseña).
- [ ] Todos los tests pasan.
- [ ] `vendor/bin/pint --test` pasa.
- [ ] `vendor/bin/phpstan analyse` nivel 6 pasa.
- [ ] `npm run typecheck` pasa.
- [ ] Lighthouse score de `/users` ≥ 85 performance (desktop).

---

## 15. Siguiente paso

Al completar task 03, habilita task 04 (tutores-pacientes) que depende de usuarios activos en la clínica.

---

## 16. Resultado

- Paquetes instalados: ninguno.
- Implementado dominio `app/Domain/User` con permisos, enums, DTOs, actions, events y policy registrada en `AuthServiceProvider`.
- Agregado CRUD de usuarios por clínica, perfil propio, roles Spatie con teams, sincronización de permisos directos por módulo activo, invitación por email usando password reset de Fortify y logs de seguridad.
- Agregadas pantallas Inertia/Vue para listado, creación, edición, detalle/permisos y perfil, con Wayfinder regenerado.
- Agregadas pruebas Pest de creación, actualización/avatar, tenancy, roles, permisos, perfil, desactivación/restauración y helpers/scopes del modelo.
- Decisión: la desactivación usa `is_active=false` sin `delete()` para cumplir "desactivación sin borrado físico"; `SoftDeletes` queda disponible en el modelo por regla global, pero no se usa para desactivar usuarios.
- Decisión: el aislamiento cross-clinic con ID exacto responde 404 por global scope de tenancy durante route model binding, evitando filtrar existencia del usuario de otra clínica.
- Issues pendientes: cierre de sesiones activas al desactivar usuario queda diferido tal como indica la task.
