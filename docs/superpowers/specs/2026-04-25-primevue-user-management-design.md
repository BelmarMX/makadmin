# Design: PrimeVue UI Integration + User Management Expansion

**Date:** 2026-04-25  
**Task ref:** Task 04 — UI/UX + User Management  
**Scope:** PrimeVue component adoption, user UX improvements, superadmin clinic-user management, clinic-role-module configuration, per-branch permissions.

---

## 1. Context

Current state:
- PrimeVue 4.x installed and configured with Aura theme + dark mode selector.
- Only `primevue/checkbox` used so far (BranchRolesEditor, PermissionGrid).
- Shadcn-vue components (Button, Input, Label, Card, etc.) still dominant.
- AppSidebarLayout has no `p-4` content wrapper → clinic pages lack inner margins.
- AdminLayout has `<div class="flex flex-1 flex-col gap-4 p-4">` wrapper → works correctly.
- User model has `branch_id` (primary, legacy single-branch) + `branchRoles` (multi-branch pivot).
- UserCard only shows `user.branch` (single). Form has redundant `branch_id` select.
- PermissionGrid renders each module as a card with 4 inline label-checkboxes.
- Roles displayed as raw strings (e.g. "clinic_admin") in several places.
- No superadmin control to edit/suspend/delete clinic users.
- No per-clinic configuration of what each role can access.
- Sidebar shows all links regardless of user permissions.

---

## 2. Design

### 2.1 Layout margin fix

**Bug:** `AppSidebarLayout.vue` renders `<slot />` directly after `AppSidebarHeader` with no padding.  
**Fix:** Wrap slot in `<div class="flex flex-1 flex-col gap-4 p-4">`. Matches AdminLayout pattern exactly.  
Affects: all Clinic pages (Users/Index, Users/Create, Users/Edit, Users/Show, Profile/Edit, Dashboard).

### 2.2 PrimeVue component integration

**Rule added to CLAUDE.md workflow section:**
> Inputs use PrimeVue `InputText` + `FloatLabel` (variant="on"). Selects use PrimeVue `Select` + `FloatLabel`. Filters use PrimeVue `Select` + `FloatLabel`. Cascading hierarchical selects use `CascadeSelect`. Badges/tags use `Chip`. Action buttons with side effects use `v-ripple` directive.

**Components to integrate:**

| Location | Replace | With |
|---|---|---|
| Users/Index filters | `<select>` native | `Select` + `FloatLabel` (branch, role, status) |
| Users/Create + Edit fields | shadcn `Input` + `Label` | PrimeVue `InputText` + `FloatLabel` |
| Users/Create + Edit selects | `<select>` native | PrimeVue `Select` + `FloatLabel` |
| Role badges everywhere | `<span class="rounded...">` | `Chip` with role icon |
| Action buttons (Guardar, Desactivar, etc.) | shadcn `Button` | shadcn `Button` + `v-ripple` directive |

**Note:** Shadcn Button, Card, Avatar kept. PrimeVue supplements, not replaces, shadcn structure.

**FloatLabel pattern:**
```vue
<FloatLabel variant="on">
  <InputText id="name" v-model="form.name" class="w-full" />
  <label for="name">Nombre</label>
</FloatLabel>
```

**Chip pattern for roles:**
```vue
<Chip :label="roleLabel(role.name)" :image="undefined">
  <template #default>
    <component :is="roleIcon(role.name)" class="h-3.5 w-3.5" />
    <span class="ml-1">{{ roleLabel(role.name) }}</span>
  </template>
</Chip>
```

**Ripple:** Add `v-ripple` to `<Button>` on submit, deactivate, restore, delete. Requires `app.directive('ripple', Ripple)` in app.ts.

### 2.3 UserCard multi-branch

**Change UserCard prop** from `branch?: {id, name}` to `branches: Array<{branch_id, role, branch_name}>`.  
Backend: `ListUsersAction` loads `branchRoles.branch` instead of `branch`.

Show in card:
```
[icon] Sucursal Centro    [Chip: Veterinario]
[icon] Sucursal Norte     [Chip: Recepcionista]
```

Remove `MapPin + branch.name` single-branch line. Remove `user.branch` from UserCard props.

### 2.4 Admin-first user ordering

`ListUsersAction::handle()` — add `orderByRaw("CASE WHEN id IN (SELECT user_id FROM model_has_roles WHERE role_id = (SELECT id FROM roles WHERE name = 'clinic_admin' AND team_id = ?) ) THEN 0 ELSE 1 END", [current_clinic()->id])` before `->orderBy('name')`.

Simpler alternative: load with roles eager, sort in PHP after paginate (not ideal for pagination accuracy). Better: raw SQL case.

Actually cleanest: add a scope `scopeAdminFirst` to User model using a subquery on `model_has_roles`.

### 2.5 Remove redundant branch_id select from forms

`Users/Create.vue` and `Users/Edit.vue` — remove the `<div class="grid gap-2">` block containing the `branch_id` select. The `BranchRolesEditor` already handles multi-branch assignment.

`syncRolePayload()` still sets `form.branch_id = form.branch_roles[0]?.branch_id` for backend compatibility.

The `branch_id` column remains on the model as the primary branch (auto-set from first branch role in action).

### 2.6 Labels in Spanish

`roleLabel()` in `userLabels.ts` already has Spanish translations. Issues:
- `BranchRolesEditor.vue`: shows `role.label` (already Spanish via `UserRole::options()`). ✓
- `UserCard.vue`: shows `role.name` raw. ✗ → use `roleLabel(role.name)`.
- `Show.vue`: uses `roleLabel(assignment.role)`. ✓
- Filter `<select>` options: use `roles` prop (already Spanish via `UserRole::options()`). ✓

Fix: wrap all role name displays with `roleLabel()`.

### 2.7 PermissionGrid 5-column table

Replace current card-per-module layout with a proper table:

```
| Módulo                           | [Eye] Ver | [Plus] Crear | [Pencil] Editar | [Trash] Eliminar |
|----------------------------------|-----------|--------------|-----------------|------------------|
| Pacientes y Tutores              |    ☑      |      ☑       |       ☐         |        ☐         |
| Gestión de dueños y mascotas...  |           |              |                 |                  |
```

Row structure: first cell = module label (bold) + description (muted small), remaining 4 cells = centered checkboxes. Header icons from lucide-vue-next.

Module description available from `ModuleKey::description()`. Pass description in module props from backend.

### 2.8 Branch click → show permissions (UserShow)

In `Show.vue`, the "Sucursales y Roles" section currently shows static cards.

New behavior: clicking a branch card selects it (active state). Below the cards, show:
- Active modules for that branch's role (from clinic-role-module config, §2.11)
- Direct permissions for that branch (from `user_branch_permissions`, §2.10)

State: `selectedBranchId = ref(user.branch_id ?? user.branch_roles[0]?.branch_id)`.

Default: primary branch pre-selected.

### 2.9 Show.vue data column improvements

Replace current "Datos" card content:

Before:
```
Sucursal: Centro
Teléfono: ...
Cédula: ...
Roles: [chip chip]
Sucursales y roles: [cards]
```

After:
```
[Phone icon] +52 55 1234 5678
[GraduationCap icon] Cédula: DVM12345

[MapPin icon] SUCURSAL CENTRO
              [Chip: Veterinario]
[MapPin icon] SUCURSAL NORTE  
              [Chip: Recepcionista]
```

Remove the top "Sucursal:" single line. Remove "Sucursales y roles:" label. Branches become the primary structure.

### 2.10 Per-branch direct permissions (backend)

New table `user_branch_permissions`:
```sql
CREATE TABLE user_branch_permissions (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    branch_id BIGINT NOT NULL REFERENCES clinic_branches(id) ON DELETE CASCADE,
    permission VARCHAR(255) NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE (user_id, branch_id, permission)
);
CREATE INDEX idx_ubp_user_branch ON user_branch_permissions(user_id, branch_id);
```

New model `UserBranchPermission`. Helper method on User:
```php
public function branchPermissions(int $branchId): Collection
{
    return $this->userBranchPermissions()
        ->where('branch_id', $branchId)
        ->pluck('permission');
}
```

`PermissionGrid` receives `branchId` prop. `UserPermissionController::update()` accepts `branch_id` in payload and syncs `user_branch_permissions` instead of (or in addition to) Spatie direct permissions.

Pass `branch_roles` with `branch_id` to frontend so PermissionGrid can be per-branch.

### 2.11 Clinic-role-module configuration (superadmin)

New table `clinic_role_modules`:
```sql
CREATE TABLE clinic_role_modules (
    id BIGSERIAL PRIMARY KEY,
    clinic_id BIGINT NOT NULL REFERENCES clinics(id) ON DELETE CASCADE,
    role VARCHAR(100) NOT NULL,
    module_key VARCHAR(100) NOT NULL,
    is_enabled BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE (clinic_id, role, module_key)
);
CREATE INDEX idx_crm_clinic_role ON clinic_role_modules(clinic_id, role);
```

New model `ClinicRoleModule`. Default behavior: if no row exists, all modules enabled (open world assumption).

**New tab in `Admin/Clinics/Show.vue`:** "Roles y Módulos" (5th tab).

Tab UI: role selector on top (tabs or segmented control), below = module list with toggle per module. Shows: which modules are enabled/disabled for that role in this clinic.

New controller: `Admin/ClinicRoleModuleController`:
- `show(clinic, role)` → returns enabled modules for role
- `update(clinic, role)` → sync `clinic_role_modules` for role

New action: `SyncClinicRoleModulesAction`.

### 2.12 Superadmin clinic user management

Add full user management to `Admin/Clinics/Show.vue` → Users tab.

Current: only invite + verify email.  
New: full CRUD with:
- **Edit user data** (name, email, phone) → slide-over or modal
- **Activate / Suspend** toggle
- **Assign roles per branch** → BranchRolesEditor in modal
- **Delete** user (soft delete, with confirmation)

New controller: `App\Http\Controllers\Admin\ClinicUserController`:
```php
index(clinic)    // already exists as part of Show
store(clinic)    // already exists: invite
update(clinic, user)
activate(clinic, user)
deactivate(clinic, user)
destroy(clinic, user)
```

Reuse `UserData`, `UpdateUserAction`, `DeactivateUserAction`, `RestoreUserAction` from `Domain/User`.

### 2.13 Sidebar permission filtering

`AppSidebar.vue` — clinic context navItems. Currently hardcoded:
```ts
{ title: 'Dashboard', href: ..., icon: LayoutGrid },
{ title: 'Usuarios', href: ..., icon: Users },
```

Add `permission` field to NavItem type. Filter navItems using `page.props.auth.permissions`:
```ts
type NavItem = {
  title: string;
  href: string;
  icon: Component;
  permission?: string;
}
```

Backend: `HandleInertiaRequests` shares `auth.permissions` array (effective permission names for current user). Filter in computed:
```ts
const visibleNavItems = computed(() =>
  navItems.value.filter(item =>
    !item.permission || permissions.includes(item.permission)
  )
);
```

Clinic admin and superadmin always see all items. Regular users see only permitted items.

### 2.14 CLAUDE.md task rule update

In section **"Flujo de trabajo"** and a new **"Componentes UI"** subsection, add:

```markdown
## Componentes UI (PrimeVue preferido)

- Inputs de texto: `InputText` + `FloatLabel` variant="on".
- Selects / dropdowns: `Select` + `FloatLabel`.  
- Filtros en listados: `Select` + `FloatLabel` (uno por filtro).
- Badges / etiquetas: `Chip` de PrimeVue con icono si aplica.
- Checkboxes: `Checkbox` de PrimeVue (binary mode).
- Botones con efecto al hacer click: agregar directiva `v-ripple`.
- Shadcn Button/Card/Avatar se mantienen para estructura de layout.
- No mezclar `<select>` nativo con PrimeVue en la misma pantalla.
- UI siempre en español latinoamericano. Nunca mostrar claves internas (e.g., `clinic_admin`) en UI.
```

---

## 3. Architecture

No new domains. Changes within existing domains:

```
Domain/User/
  Models/UserBranchPermission.php     (new)
  Actions/SyncBranchPermissionsAction.php (new)

Domain/Clinic/
  Models/ClinicRoleModule.php         (new)
  Actions/SyncClinicRoleModulesAction.php (new)

Http/Controllers/Admin/
  ClinicUserController.php            (new — CRUD for clinic users from admin)
  ClinicRoleModuleController.php      (new)

Http/Controllers/Clinic/
  UserPermissionController.php        (modify — accept branch_id)

resources/js/
  components/domain/User/
    UserCard.vue                      (modify — multi-branch)
    PermissionGrid.vue                (redesign — 5-col table)
    BranchRolesEditor.vue             (keep — already correct)
  pages/Clinic/Users/
    Index.vue                         (PrimeVue filters, Chip roles)
    Create.vue                        (FloatLabel inputs, remove branch select)
    Edit.vue                          (FloatLabel inputs, remove branch select)
    Show.vue                          (branch click, grouped data, Chip)
  pages/Admin/Clinics/
    Show.vue                          (user management tab, role-module tab)
  components/AppSidebar.vue           (permission filtering)
  layouts/app/AppSidebarLayout.vue    (add p-4 wrapper)
  app.ts                              (register Ripple directive)
  lib/userLabels.ts                   (no change needed — already correct)
```

---

## 4. Data flow: branch permissions

```
UserShow page load:
  props.user.branch_roles = [{branch_id, role, branch: {id, name}}, ...]
  props.user.user_branch_permissions = [{branch_id, permission}, ...]
  props.effectiveModules = clinic_role_modules for user's roles

User clicks branch card:
  selectedBranchId = branch.id
  → computed activeModules = effectiveModules filtered to this branch's role
  → computed branchDirectPermissions = user_branch_permissions filtered to branch_id

PermissionGrid receives:
  branchId = selectedBranchId
  permissions = branchDirectPermissions for that branch
  modules (filtered to activeModules)
```

---

## 5. Backend migration list

1. `create_user_branch_permissions_table` — user_id, branch_id, permission
2. `create_clinic_role_modules_table` — clinic_id, role, module_key, is_enabled

No modification to existing tables. No `migrate:fresh`.

---

## 6. Implementation phases (for Codex)

### Phase 1 — Layout + PrimeVue base (no backend)
- Fix `AppSidebarLayout.vue` padding
- Register `Ripple` directive in `app.ts`
- Update `CLAUDE.md` UI rules

### Phase 2 — PrimeVue inputs + forms
- `Users/Create.vue` + `Users/Edit.vue`: FloatLabel inputs, remove branch_id select
- `Users/Index.vue`: PrimeVue Select + FloatLabel for 3 filters

### Phase 3 — User list + card improvements
- `UserCard.vue`: multi-branch display, Chip roles, roleLabel()
- `ListUsersAction`: load branchRoles.branch, admin-first ordering

### Phase 4 — PermissionGrid redesign
- `PermissionGrid.vue`: 5-column table, pass module description from backend

### Phase 5 — UserShow improvements
- `Show.vue`: branch click interaction, grouped data column, Chip roles, icons for data fields
- Backend: pass `user_branch_permissions` and `effectiveModules` in UserController::show()

### Phase 6 — Migrations + per-branch permissions backend
- Migration: `user_branch_permissions`
- Model `UserBranchPermission`
- `UserPermissionController::update()` accepts branch_id
- `SyncBranchPermissionsAction`

### Phase 7 — Clinic-role-module config (superadmin)
- Migration: `clinic_role_modules`
- Model `ClinicRoleModule`
- `ClinicRoleModuleController`
- `SyncClinicRoleModulesAction`
- New tab "Roles y Módulos" in `Admin/Clinics/Show.vue`

### Phase 8 — Superadmin clinic user management
- `ClinicUserController` (CRUD)
- Expand Users tab in `Admin/Clinics/Show.vue` with edit/activate/deactivate/delete
- Reuse existing User domain actions

### Phase 9 — Sidebar permission filtering
- `AppSidebar.vue` navItems filtered by `auth.permissions`
- `HandleInertiaRequests` shares permissions array
- Clinic admin + superadmin bypass filter
