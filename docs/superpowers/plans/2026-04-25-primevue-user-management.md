# PrimeVue UI Integration + User Management Expansion

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Integrate PrimeVue components across user management UI, fix layout margins, add multi-branch user display, redesign permission grid, add per-branch permissions, add superadmin clinic-user management, and add clinic-role-module configuration.

**Architecture:** 9 phases from pure-frontend fixes up to new backend features (2 new tables, 2 new controllers). All changes additive — no existing tables modified, no `migrate:fresh`. PrimeVue supplements shadcn-vue: Button/Card/Avatar stay shadcn, inputs/selects/chips/checkboxes move to PrimeVue.

**Tech Stack:** Laravel 13, PHP 8.3, Vue 3 + TypeScript, Inertia v3, PrimeVue 4 (Aura theme), Tailwind CSS, Spatie Permission (teams), Pest

---

## File Map

### New files
- `database/migrations/2026_04_25_100000_create_user_branch_permissions_table.php`
- `database/migrations/2026_04_25_100001_create_clinic_role_modules_table.php`
- `app/Domain/User/Models/UserBranchPermission.php`
- `app/Domain/Clinic/Models/ClinicRoleModule.php`
- `app/Domain/User/Actions/SyncBranchPermissionsAction.php`
- `app/Domain/Clinic/Actions/SyncClinicRoleModulesAction.php`
- `app/Http/Controllers/Admin/ClinicUserController.php`
- `app/Http/Controllers/Admin/ClinicRoleModuleController.php`
- `app/Http/Requests/Admin/UpdateClinicUserRequest.php`
- `app/Http/Requests/Admin/SyncClinicRoleModulesRequest.php`
- `app/Http/Requests/Clinic/SyncBranchPermissionsRequest.php`
- `tests/Feature/Admin/ClinicUserManagementTest.php`
- `tests/Feature/Admin/ClinicRoleModuleTest.php`
- `tests/Feature/Clinic/BranchPermissionsTest.php`

### Modified files
- `resources/js/layouts/app/AppSidebarLayout.vue` — add `p-4` content wrapper
- `resources/js/app.ts` — register `Ripple` directive
- `CLAUDE.md` — add PrimeVue UI rules section
- `resources/js/components/AppSidebar.vue` — filter nav items by permission
- `resources/js/components/domain/User/UserCard.vue` — multi-branch, Chip roles
- `resources/js/components/domain/User/PermissionGrid.vue` — 5-column table, branch-aware
- `resources/js/pages/Clinic/Users/Index.vue` — PrimeVue filters, Chip roles
- `resources/js/pages/Clinic/Users/Create.vue` — FloatLabel inputs, remove branch select
- `resources/js/pages/Clinic/Users/Edit.vue` — FloatLabel inputs, remove branch select
- `resources/js/pages/Clinic/Users/Show.vue` — branch-click interaction, grouped data
- `resources/js/pages/Admin/Clinics/Show.vue` — user management tab + role-module tab
- `resources/js/types/auth.ts` — add `permissions` and `is_super_admin` to Auth/User types
- `app/Http/Middleware/HandleInertiaRequests.php` — share `auth.permissions`
- `app/Http/Controllers/Clinic/UserPermissionController.php` — accept branch_id
- `app/Http/Requests/Clinic/SyncUserPermissionsRequest.php` — allow branch_id
- `app/Http/Requests/Clinic/UpdateUserRequest.php` — make branch_id derived from branch_roles
- `app/Domain/User/Actions/ListUsersAction.php` — clinic_admin-first ordering
- `app/Domain/User/Actions/UpdateUserAction.php` — handle branch_id derived from branch_roles
- `app/Http/Controllers/Clinic/UserController.php` — pass branch permissions + effective modules
- `routes/admin.php` — add clinic user management + role-module routes

---

## Task 1: Fix AppSidebarLayout padding + register Ripple + update CLAUDE.md

**Files:**
- Modify: `resources/js/layouts/app/AppSidebarLayout.vue`
- Modify: `resources/js/app.ts`
- Modify: `CLAUDE.md`

- [ ] **Step 1: Fix AppSidebarLayout padding**

Open `resources/js/layouts/app/AppSidebarLayout.vue`. The current template ends with `<slot />` directly after `<AppSidebarHeader>`. Wrap it:

```vue
<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <div class="flex flex-1 flex-col gap-4 p-4">
                <slot />
            </div>
        </AppContent>
        <Toaster />
    </AppShell>
</template>
```

- [ ] **Step 2: Register Ripple directive in app.ts**

Open `resources/js/app.ts`. Add import and register:

```ts
import { createInertiaApp } from '@inertiajs/vue3';
import Aura from '@primeuix/themes/aura';
import PrimeVue from 'primevue/config';
import Ripple from 'primevue/ripple';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    progress: {
        color: '#4B5563',
    },
    withApp: (app) => {
        app.use(PrimeVue, {
            theme: {
                preset: Aura,
                options: {
                    darkModeSelector: '.dark',
                },
            },
        });
        app.directive('ripple', Ripple);
    },
});

initializeTheme();
initializeFlashToast();
```

- [ ] **Step 3: Add PrimeVue UI rules to CLAUDE.md**

In `CLAUDE.md`, after the `## Anti-patrones prohibidos` section, add:

```markdown
## Componentes UI (PrimeVue preferido)

- Inputs de texto: `InputText` + `FloatLabel` variant="on".
- Selects / dropdowns: `Select` + `FloatLabel` (uno por filtro, no `<select>` nativo).
- Badges / etiquetas de roles y estado: `Chip` de PrimeVue con icono si aplica.
- Checkboxes: `Checkbox` de PrimeVue en modo `binary`.
- Botones con efecto de clic: agregar directiva `v-ripple` a acciones importantes (guardar, activar, desactivar, eliminar).
- Shadcn Button / Card / Avatar / Badge se mantienen para estructura de layout.
- No mezclar `<select>` nativo con PrimeVue en la misma vista.
- UI siempre en español latinoamericano. Nunca mostrar claves internas (ej. `clinic_admin`, `veterinarian`) directamente al usuario; usar `roleLabel()` de `@/lib/userLabels`.
```

- [ ] **Step 4: Build and type-check**

```bash
cd /Volumes/Thunder/www/makadmin
npm run build
npm run typecheck
```

Expected: no errors.

- [ ] **Step 5: Commit**

```bash
git add resources/js/layouts/app/AppSidebarLayout.vue resources/js/app.ts CLAUDE.md
git commit -m "feat: fix clinic layout margins, register PrimeVue Ripple, add UI rules to CLAUDE.md"
```

---

## Task 2: PrimeVue filters on Users/Index + Chip role badges

**Files:**
- Modify: `resources/js/pages/Clinic/Users/Index.vue`

- [ ] **Step 1: Replace filters and role display in Index.vue**

Replace the entire file content:

```vue
<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Search, X, Users } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import Select from 'primevue/select';
import FloatLabel from 'primevue/floatlabel';
import UserCard from '@/components/domain/User/UserCard.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import * as userRoutes from '@/actions/App/Http/Controllers/Clinic/UserController';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    users: {
        data: Array<{
            id: number;
            name: string;
            email: string;
            phone?: string | null;
            avatar?: string | null;
            is_active: boolean;
            branch_roles: Array<{ branch_id: number; role: string; branch: { id: number; name: string } | null }>;
        }>;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    branches: Array<{ id: number; name: string }>;
    roles: Array<{ value: string; label: string }>;
    filters: { search: string; branch_id?: number | null; role?: string | null; status?: string | null };
}>();

const clinic = window.location.hostname.split('.')[0];
const search = ref(props.filters.search ?? '');
const branchId = ref<number | null>(props.filters.branch_id ?? null);
const role = ref<string | null>(props.filters.role ?? null);
const status = ref<string | null>(props.filters.status ?? null);
let debounceTimer: ReturnType<typeof setTimeout>;

const branchOptions = [{ id: null, name: 'Todas las sucursales' }, ...props.branches];
const roleOptions = [{ value: null, label: 'Todos los roles' }, ...props.roles];
const statusOptions = [
    { value: null, label: 'Todos' },
    { value: 'active', label: 'Activos' },
    { value: 'inactive', label: 'Inactivos' },
];

watch([search, branchId, role, status], () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        router.get(
            userRoutes.index(clinic).url,
            {
                search: search.value || undefined,
                branch_id: branchId.value || undefined,
                role: role.value || undefined,
                status: status.value || undefined,
            },
            { preserveState: true, replace: true },
        );
    }, 300);
});

function clearFilters() {
    search.value = '';
    branchId.value = null;
    role.value = null;
    status.value = null;
}
</script>

<template>
    <Head title="Usuarios" />

    <div class="space-y-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Usuarios</h1>
                <p class="text-sm text-muted-foreground">Equipo, roles y accesos de la clínica.</p>
            </div>
            <Button as-child v-ripple>
                <Link :href="userRoutes.create(clinic).url">
                    <Plus class="h-4 w-4" />
                    Nuevo usuario
                </Link>
            </Button>
        </div>

        <div class="grid gap-3 xl:grid-cols-[1fr_220px_220px_180px]">
            <div class="relative">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input v-model="search" placeholder="Buscar por nombre, email o teléfono…" class="pl-9 pr-9" />
                <button
                    v-if="search"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                    @click="search = ''"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>

            <FloatLabel variant="on">
                <Select
                    v-model="branchId"
                    :options="branchOptions"
                    option-label="name"
                    option-value="id"
                    input-id="filter-branch"
                    class="w-full"
                />
                <label for="filter-branch">Sucursal</label>
            </FloatLabel>

            <FloatLabel variant="on">
                <Select
                    v-model="role"
                    :options="roleOptions"
                    option-label="label"
                    option-value="value"
                    input-id="filter-role"
                    class="w-full"
                />
                <label for="filter-role">Rol</label>
            </FloatLabel>

            <FloatLabel variant="on">
                <Select
                    v-model="status"
                    :options="statusOptions"
                    option-label="label"
                    option-value="value"
                    input-id="filter-status"
                    class="w-full"
                />
                <label for="filter-status">Estado</label>
            </FloatLabel>
        </div>

        <div
            v-if="users.data.length === 0"
            class="flex flex-col items-center justify-center rounded-lg border border-dashed border-border py-16 text-center"
        >
            <Users class="mb-4 h-12 w-12 text-muted-foreground" />
            <p class="font-medium text-foreground">
                {{ search ? 'Sin resultados para "' + search + '"' : 'Sin usuarios registrados' }}
            </p>
            <p class="text-sm text-muted-foreground">
                {{ search ? 'Ajusta los filtros para ampliar la búsqueda.' : 'Agrega el primer usuario de la clínica.' }}
            </p>
            <Button variant="ghost" class="mt-3" @click="clearFilters">
                <X class="h-4 w-4" />
                Limpiar filtros
            </Button>
        </div>

        <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2 2xl:grid-cols-4">
            <UserCard v-for="user in users.data" :key="user.id" :user="user" />
        </div>

        <div v-if="users.links.length > 3" class="flex justify-center gap-1">
            <template v-for="link in users.links" :key="link.label">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    :class="['rounded border px-3 py-1 text-sm transition-colors', link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted']"
                    v-html="link.label"
                />
                <span v-else class="rounded border border-transparent px-3 py-1 text-sm text-muted-foreground" v-html="link.label" />
            </template>
        </div>
    </div>
</template>
```

- [ ] **Step 2: Build and type-check**

```bash
npm run build && npm run typecheck
```

Expected: no errors.

- [ ] **Step 3: Commit**

```bash
git add resources/js/pages/Clinic/Users/Index.vue
git commit -m "feat: PrimeVue Select+FloatLabel filters on Users/Index"
```

---

## Task 3: Update UserCard for multi-branch + Chip roles + admin-first ordering

**Files:**
- Modify: `resources/js/components/domain/User/UserCard.vue`
- Modify: `app/Domain/User/Actions/ListUsersAction.php`

- [ ] **Step 1: Update UserCard.vue**

Replace full file:

```vue
<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Mail, MapPin, Pencil, BadgeCheck, CircleDollarSign, ConciergeBell, Scissors, ShieldCheck, Stethoscope } from 'lucide-vue-next';
import type { Component } from 'vue';
import Chip from 'primevue/chip';
import UserStatusBadge from '@/components/domain/User/UserStatusBadge.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import * as userRoutes from '@/actions/App/Http/Controllers/Clinic/UserController';
import { roleLabel } from '@/lib/userLabels';

defineProps<{
    user: {
        id: number;
        name: string;
        email: string;
        phone?: string | null;
        avatar?: string | null;
        is_active: boolean;
        branch_roles: Array<{ branch_id: number; role: string; branch: { id: number; name: string } | null }>;
    };
}>();

const clinic = window.location.hostname.split('.')[0];

const roleIcons: Record<string, Component> = {
    clinic_admin: ShieldCheck,
    veterinarian: Stethoscope,
    groomer: Scissors,
    receptionist: ConciergeBell,
    cashier: CircleDollarSign,
};

function roleIcon(role: string): Component {
    return roleIcons[role] ?? BadgeCheck;
}

function initials(name: string) {
    return name
        .split(' ')
        .filter(Boolean)
        .map((word) => word[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();
}

type BranchGroup = { branch: { id: number; name: string }; roles: string[] };

function groupedBranches(branchRoles: Array<{ branch_id: number; role: string; branch: { id: number; name: string } | null }>): BranchGroup[] {
    const map = new Map<number, BranchGroup>();
    for (const br of branchRoles) {
        if (!br.branch) continue;
        const existing = map.get(br.branch_id);
        if (existing) {
            existing.roles.push(br.role);
        } else {
            map.set(br.branch_id, { branch: br.branch, roles: [br.role] });
        }
    }
    return [...map.values()];
}
</script>

<template>
    <Card>
        <CardContent class="flex items-start gap-4 p-4">
            <Avatar class="h-12 w-12 shrink-0">
                <AvatarImage v-if="user.avatar" :src="user.avatar" :alt="user.name" />
                <AvatarFallback>{{ initials(user.name) }}</AvatarFallback>
            </Avatar>

            <div class="min-w-0 flex-1 space-y-2">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <Link :href="userRoutes.show({ clinic, user: user.id }).url" class="font-medium text-foreground hover:underline">
                            {{ user.name }}
                        </Link>
                        <div class="mt-1 flex items-center gap-2 text-sm text-muted-foreground">
                            <Mail class="h-3.5 w-3.5 shrink-0" />
                            <span class="truncate">{{ user.email }}</span>
                        </div>
                    </div>
                    <UserStatusBadge :active="user.is_active" />
                </div>

                <div class="space-y-1.5">
                    <div
                        v-for="group in groupedBranches(user.branch_roles)"
                        :key="group.branch.id"
                        class="space-y-1"
                    >
                        <div class="flex items-center gap-1 text-xs font-medium text-muted-foreground">
                            <MapPin class="h-3.5 w-3.5 shrink-0" />
                            <span class="truncate uppercase tracking-wide">{{ group.branch.name }}</span>
                        </div>
                        <div class="flex flex-wrap gap-1 pl-4">
                            <Chip
                                v-for="role in group.roles"
                                :key="role"
                                :label="roleLabel(role)"
                                class="!text-xs !py-0.5 !px-2"
                            >
                                <template #default>
                                    <component :is="roleIcon(role)" class="h-3 w-3 shrink-0" />
                                    <span class="ml-1 text-xs">{{ roleLabel(role) }}</span>
                                </template>
                            </Chip>
                        </div>
                    </div>
                    <p v-if="groupedBranches(user.branch_roles).length === 0" class="text-xs text-muted-foreground">Sin sucursal asignada</p>
                </div>
            </div>

            <Button variant="ghost" size="icon" as-child>
                <Link :href="userRoutes.edit({ clinic, user: user.id }).url" title="Editar usuario">
                    <Pencil class="h-4 w-4" />
                </Link>
            </Button>
        </CardContent>
    </Card>
</template>
```

- [ ] **Step 2: Update ListUsersAction for admin-first and multi-branch eager load**

Replace `app/Domain/User/Actions/ListUsersAction.php`:

```php
<?php

namespace App\Domain\User\Actions;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class ListUsersAction
{
    public function handle(Request $request): LengthAwarePaginator
    {
        $search = $request->string('search')->trim()->toString();
        $clinicId = current_clinic()->id;

        return User::query()
            ->with(['branchRoles' => fn ($q) => $q->with('branch:id,name')])
            ->when($request->integer('branch_id') > 0, fn ($query) => $query->whereHas(
                'branchRoles',
                fn ($q) => $q->where('branch_id', $request->integer('branch_id'))
            ))
            ->when($request->filled('role'), fn ($query) => $query->role($request->string('role')->toString()))
            ->when($request->string('status')->toString() === 'active', fn ($query) => $query->active())
            ->when($request->string('status')->toString() === 'inactive', fn ($query) => $query->inactive())
            ->when($search !== '', fn ($query) => $query->where(fn ($subquery) => $subquery
                ->where('name', 'ilike', "%{$search}%")
                ->orWhere('email', 'ilike', "%{$search}%")
                ->orWhere('phone', 'ilike', "%{$search}%")
            ))
            ->orderByRaw(
                "CASE WHEN id IN (
                    SELECT ubr.user_id FROM user_branch_roles ubr
                    INNER JOIN roles r ON r.name = ubr.role AND r.team_id = ?
                    WHERE ubr.clinic_id = ? AND ubr.role = 'clinic_admin' AND ubr.deleted_at IS NULL
                ) THEN 0 ELSE 1 END",
                [$clinicId, $clinicId]
            )
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();
    }
}
```

- [ ] **Step 3: Verify tests pass**

```bash
php artisan test --parallel --filter=UserTest
```

Expected: all existing user tests pass.

- [ ] **Step 4: Build and type-check**

```bash
npm run build && npm run typecheck
```

Expected: no errors.

- [ ] **Step 5: Commit**

```bash
git add resources/js/components/domain/User/UserCard.vue app/Domain/User/Actions/ListUsersAction.php
git commit -m "feat: UserCard multi-branch display with PrimeVue Chip, admin-first ordering"
```

---

## Task 4: FloatLabel inputs on Create/Edit user forms + remove redundant branch select

**Files:**
- Modify: `resources/js/pages/Clinic/Users/Create.vue`
- Modify: `resources/js/pages/Clinic/Users/Edit.vue`
- Modify: `app/Http/Requests/Clinic/UpdateUserRequest.php`

- [ ] **Step 1: Update Create.vue with FloatLabel inputs, remove branch_id select**

Replace the template's field grid section (inside `<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">`):

```vue
<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from 'lucide-vue-next';
import { ref } from 'vue';
import InputText from 'primevue/inputtext';
import FloatLabel from 'primevue/floatlabel';
import Password from 'primevue/password';
import CropModal from '@/components/CropModal.vue';
import ImageUploadCircle from '@/components/ImageUploadCircle.vue';
import BranchRolesEditor from '@/components/domain/User/BranchRolesEditor.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import * as userRoutes from '@/actions/App/Http/Controllers/Clinic/UserController';

defineOptions({ layout: AppLayout });

defineProps<{
    branches: Array<{ id: number; name: string }>;
    roles: Array<{ value: string; label: string }>;
}>();

const clinic = window.location.hostname.split('.')[0];
const cropOpen = ref(false);
const cropSrc = ref<string | null>(null);
const avatarPreview = ref<string | null>(null);

type BranchRole = { branch_id: number; roles: string[] };

const form = useForm<{
    name: string;
    email: string;
    phone: string;
    branch_id: string;
    professional_license: string;
    password: string;
    password_confirmation: string;
    avatar: File | null;
    roles: string[];
    branch_roles: BranchRole[];
}>({
    name: '',
    email: '',
    phone: '',
    branch_id: '',
    professional_license: '',
    password: '',
    password_confirmation: '',
    avatar: null,
    roles: [],
    branch_roles: [],
});

function syncRolePayload() {
    form.roles = [...new Set(form.branch_roles.flatMap((assignment) => assignment.roles))];
    form.branch_id = form.branch_roles[0]?.branch_id ? String(form.branch_roles[0].branch_id) : '';
}

function onFileSelected(file: File) {
    cropSrc.value = URL.createObjectURL(file);
    cropOpen.value = true;
}

function onCropConfirm(blob: Blob) {
    cropOpen.value = false;
    form.avatar = new File([blob], 'avatar.webp', { type: 'image/webp' });
    avatarPreview.value = URL.createObjectURL(form.avatar);
}

function removeSelectedAvatar() {
    avatarPreview.value = null;
    form.avatar = null;
}

function submit() {
    syncRolePayload();
    form.post(userRoutes.store(clinic).url, { forceFormData: true });
}
</script>

<template>
    <Head title="Nuevo usuario" />

    <form class="space-y-6" @submit.prevent="submit">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Nuevo usuario</h1>
                <p class="text-sm text-muted-foreground">Alta de integrante con roles de clínica.</p>
            </div>
            <div class="flex gap-2">
                <Button variant="outline" as-child>
                    <Link :href="userRoutes.index(clinic).url">
                        <ArrowLeft class="h-4 w-4" />
                        Volver
                    </Link>
                </Button>
                <Button :disabled="form.processing" v-ripple>
                    <Save class="h-4 w-4" />
                    Guardar
                </Button>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-[12rem_1fr]">
            <div class="flex justify-center md:justify-start">
                <ImageUploadCircle
                    :model-value="avatarPreview"
                    size="lg"
                    label="Avatar"
                    :error="form.errors.avatar"
                    @upload="onFileSelected"
                    @remove="removeSelectedAvatar"
                />
                <CropModal
                    :open="cropOpen"
                    :image-src="cropSrc"
                    @confirm="onCropConfirm"
                    @cancel="cropOpen = false"
                    @update:open="cropOpen = $event"
                />
            </div>
            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                <div class="grid gap-1">
                    <FloatLabel variant="on">
                        <InputText id="name" v-model="form.name" autocomplete="name" class="w-full" />
                        <label for="name">Nombre</label>
                    </FloatLabel>
                    <InputError :message="form.errors.name" />
                </div>
                <div class="grid gap-1">
                    <FloatLabel variant="on">
                        <InputText id="email" v-model="form.email" type="email" autocomplete="email" class="w-full" />
                        <label for="email">Email</label>
                    </FloatLabel>
                    <InputError :message="form.errors.email" />
                </div>
                <div class="grid gap-1">
                    <FloatLabel variant="on">
                        <InputText id="phone" v-model="form.phone" class="w-full" />
                        <label for="phone">Teléfono</label>
                    </FloatLabel>
                    <InputError :message="form.errors.phone" />
                </div>
                <div class="grid gap-1">
                    <FloatLabel variant="on">
                        <InputText id="license" v-model="form.professional_license" class="w-full" />
                        <label for="license">Cédula profesional</label>
                    </FloatLabel>
                    <InputError :message="form.errors.professional_license" />
                </div>
                <div class="grid gap-1">
                    <FloatLabel variant="on">
                        <Password id="password" v-model="form.password" autocomplete="new-password" :feedback="false" toggle-mask class="w-full" input-class="w-full" />
                        <label for="password">Contraseña temporal</label>
                    </FloatLabel>
                    <InputError :message="form.errors.password" />
                </div>
                <div class="grid gap-1">
                    <FloatLabel variant="on">
                        <Password id="password_confirmation" v-model="form.password_confirmation" autocomplete="new-password" :feedback="false" toggle-mask class="w-full" input-class="w-full" />
                        <label for="password_confirmation">Confirmar contraseña</label>
                    </FloatLabel>
                </div>
            </div>
        </div>

        <BranchRolesEditor v-model="form.branch_roles" :branches="branches" :roles="roles" :error="form.errors.roles || form.errors.branch_roles" />
    </form>
</template>
```

- [ ] **Step 2: Update Edit.vue with FloatLabel inputs, remove branch_id select**

Replace `resources/js/pages/Clinic/Users/Edit.vue` with:

```vue
<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from 'lucide-vue-next';
import { ref } from 'vue';
import InputText from 'primevue/inputtext';
import FloatLabel from 'primevue/floatlabel';
import Password from 'primevue/password';
import CropModal from '@/components/CropModal.vue';
import ImageUploadCircle from '@/components/ImageUploadCircle.vue';
import BranchRolesEditor from '@/components/domain/User/BranchRolesEditor.vue';
import InputError from '@/components/InputError.vue';
import UserStatusBadge from '@/components/domain/User/UserStatusBadge.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import * as userRoutes from '@/actions/App/Http/Controllers/Clinic/UserController';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    user: {
        id: number;
        name: string;
        email: string;
        phone?: string | null;
        avatar?: string | null;
        professional_license?: string | null;
        branch_id?: number | null;
        is_active: boolean;
        roles?: Array<{ name: string }>;
        branch_roles?: Array<{ branch_id: number; role: string }>;
    };
    branches: Array<{ id: number; name: string }>;
    roles: Array<{ value: string; label: string }>;
}>();

const clinic = window.location.hostname.split('.')[0];
const cropOpen = ref(false);
const cropSrc = ref<string | null>(null);
const avatarPreview = ref<string | null>(props.user.avatar ?? null);

type BranchRole = { branch_id: number; roles: string[] };

function initialBranchRoles(): BranchRole[] {
    const grouped = new Map<number, string[]>();
    for (const assignment of props.user.branch_roles ?? []) {
        grouped.set(assignment.branch_id, [...(grouped.get(assignment.branch_id) ?? []), assignment.role]);
    }
    if (grouped.size === 0 && props.user.branch_id) {
        grouped.set(props.user.branch_id, (props.user.roles ?? []).map((role) => role.name));
    }
    return [...grouped.entries()].map(([branch_id, roles]) => ({ branch_id, roles }));
}

const form = useForm<{
    name: string;
    email: string;
    phone: string;
    branch_id: string;
    professional_license: string;
    password: string;
    password_confirmation: string;
    avatar: File | null;
    roles: string[];
    branch_roles: BranchRole[];
}>({
    name: props.user.name,
    email: props.user.email,
    phone: props.user.phone ?? '',
    branch_id: props.user.branch_id ? String(props.user.branch_id) : '',
    professional_license: props.user.professional_license ?? '',
    password: '',
    password_confirmation: '',
    avatar: null,
    roles: (props.user.roles ?? []).map((role) => role.name),
    branch_roles: initialBranchRoles(),
});

function syncRolePayload() {
    form.roles = [...new Set(form.branch_roles.flatMap((assignment) => assignment.roles))];
    form.branch_id = form.branch_roles[0]?.branch_id ? String(form.branch_roles[0].branch_id) : '';
}

function onFileSelected(file: File) {
    cropSrc.value = URL.createObjectURL(file);
    cropOpen.value = true;
}

function onCropConfirm(blob: Blob) {
    cropOpen.value = false;
    form.avatar = new File([blob], 'avatar.webp', { type: 'image/webp' });
    avatarPreview.value = URL.createObjectURL(form.avatar);
}

function removeSelectedAvatar() {
    form.avatar = null;
    avatarPreview.value = props.user.avatar ?? null;
}

function submit() {
    syncRolePayload();
    form.transform((data) => ({ ...data, _method: 'PUT' })).post(userRoutes.update({ clinic, user: props.user.id }).url, {
        forceFormData: true,
    });
}
</script>

<template>
    <Head :title="`Editar ${user.name}`" />

    <form class="space-y-6" @submit.prevent="submit">
        <div class="flex items-center justify-between gap-4">
            <div>
                <div class="mb-2"><UserStatusBadge :active="user.is_active" /></div>
                <h1 class="text-2xl font-bold text-foreground">Editar usuario</h1>
                <p class="text-sm text-muted-foreground">{{ user.email }}</p>
            </div>
            <div class="flex gap-2">
                <Button variant="outline" as-child>
                    <Link :href="userRoutes.show({ clinic, user: user.id }).url">
                        <ArrowLeft class="h-4 w-4" />
                        Volver
                    </Link>
                </Button>
                <Button :disabled="form.processing" v-ripple>
                    <Save class="h-4 w-4" />
                    Guardar
                </Button>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-[12rem_1fr]">
            <div class="flex justify-center md:justify-start">
                <ImageUploadCircle
                    :model-value="avatarPreview"
                    size="lg"
                    label="Avatar"
                    :error="form.errors.avatar"
                    @upload="onFileSelected"
                    @remove="removeSelectedAvatar"
                />
                <CropModal
                    :open="cropOpen"
                    :image-src="cropSrc"
                    @confirm="onCropConfirm"
                    @cancel="cropOpen = false"
                    @update:open="cropOpen = $event"
                />
            </div>
            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                <div class="grid gap-1">
                    <FloatLabel variant="on">
                        <InputText id="name" v-model="form.name" autocomplete="name" class="w-full" />
                        <label for="name">Nombre</label>
                    </FloatLabel>
                    <InputError :message="form.errors.name" />
                </div>
                <div class="grid gap-1">
                    <FloatLabel variant="on">
                        <InputText id="email" v-model="form.email" type="email" autocomplete="email" class="w-full" />
                        <label for="email">Email</label>
                    </FloatLabel>
                    <InputError :message="form.errors.email" />
                </div>
                <div class="grid gap-1">
                    <FloatLabel variant="on">
                        <InputText id="phone" v-model="form.phone" class="w-full" />
                        <label for="phone">Teléfono</label>
                    </FloatLabel>
                    <InputError :message="form.errors.phone" />
                </div>
                <div class="grid gap-1">
                    <FloatLabel variant="on">
                        <InputText id="license" v-model="form.professional_license" class="w-full" />
                        <label for="license">Cédula profesional</label>
                    </FloatLabel>
                    <InputError :message="form.errors.professional_license" />
                </div>
                <div class="grid gap-1">
                    <FloatLabel variant="on">
                        <Password id="password" v-model="form.password" autocomplete="new-password" :feedback="false" toggle-mask class="w-full" input-class="w-full" />
                        <label for="password">Nueva contraseña</label>
                    </FloatLabel>
                    <InputError :message="form.errors.password" />
                </div>
                <div class="grid gap-1">
                    <FloatLabel variant="on">
                        <Password id="password_confirmation" v-model="form.password_confirmation" autocomplete="new-password" :feedback="false" toggle-mask class="w-full" input-class="w-full" />
                        <label for="password_confirmation">Confirmar contraseña</label>
                    </FloatLabel>
                </div>
            </div>
        </div>

        <BranchRolesEditor v-model="form.branch_roles" :branches="branches" :roles="roles" :error="form.errors.roles || form.errors.branch_roles" />
    </form>
</template>
```

- [ ] **Step 3: Update UpdateUserRequest to make branch_id optional (derived)**

Replace `app/Http/Requests/Clinic/UpdateUserRequest.php`:

```php
<?php

namespace App\Http\Requests\Clinic;

use App\Domain\User\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user instanceof User && $this->user()?->can('update', $user) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:200'],
            'email' => ['required', 'email', 'max:200', Rule::unique('users', 'email')->ignore($user?->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'branch_id' => [
                'nullable',
                Rule::exists('clinic_branches', 'id')->where('clinic_id', current_clinic()->id),
            ],
            'professional_license' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:10', 'confirmed'],
            'avatar' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'roles' => ['nullable', 'array'],
            'roles.*' => [Rule::enum(UserRole::class)],
            'branch_roles' => ['nullable', 'array', 'min:1'],
            'branch_roles.*.branch_id' => [
                'required',
                Rule::exists('clinic_branches', 'id')->where('clinic_id', current_clinic()->id),
            ],
            'branch_roles.*.roles' => ['required', 'array', 'min:1'],
            'branch_roles.*.roles.*' => [Rule::enum(UserRole::class)],
        ];
    }
}
```

- [ ] **Step 4: Run tests**

```bash
php artisan test --parallel --filter=UserTest
```

Expected: all user tests pass.

- [ ] **Step 5: Build and type-check**

```bash
npm run build && npm run typecheck
```

Expected: no errors.

- [ ] **Step 6: Commit**

```bash
git add resources/js/pages/Clinic/Users/Create.vue resources/js/pages/Clinic/Users/Edit.vue app/Http/Requests/Clinic/UpdateUserRequest.php
git commit -m "feat: FloatLabel PrimeVue inputs on user forms, remove redundant branch select"
```

---

## Task 5: Redesign PermissionGrid as 5-column table

**Files:**
- Modify: `resources/js/components/domain/User/PermissionGrid.vue`
- Modify: `app/Http/Controllers/Clinic/UserController.php` — pass description in modules prop

- [ ] **Step 1: Update UserController to pass module description**

In `app/Http/Controllers/Clinic/UserController.php`, change `formProps()`:

```php
private function formProps(): array
{
    return [
        'branches' => current_clinic()->branches()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        'roles' => UserRole::options(),
        'modules' => current_clinic()->modules()->where('is_active', true)->orderBy('module_key')->get(['module_key'])->map(function ($module): array {
            $moduleKey = (string) $module->getAttribute('module_key');
            $moduleEnum = ModuleKey::tryFrom($moduleKey);

            return [
                'module_key' => $moduleKey,
                'label' => $moduleEnum?->label() ?? $moduleKey,
                'description' => $moduleEnum?->description() ?? '',
            ];
        }),
    ];
}
```

- [ ] **Step 2: Redesign PermissionGrid.vue as 5-column table**

Replace full file:

```vue
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import Checkbox from 'primevue/checkbox';
import { Eye, Plus, Pencil, Trash2, Save } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    action: string;
    modules: Array<{ module_key: string; label: string; description?: string }>;
    permissions?: Array<{ name: string }>;
    branchId?: number | null;
}>();

const actions = [
    { key: 'view', label: 'Ver', icon: Eye },
    { key: 'create', label: 'Crear', icon: Plus },
    { key: 'update', label: 'Editar', icon: Pencil },
    { key: 'delete', label: 'Eliminar', icon: Trash2 },
];

const initialPermissions = new Set((props.permissions ?? []).map((p) => p.name));
const permissions: Record<string, string[]> = {};

for (const module of props.modules) {
    permissions[module.module_key] = actions
        .filter((action) => initialPermissions.has(`${module.module_key}.${action.key}`))
        .map((action) => action.key);
}

const form = useForm({ permissions, branch_id: props.branchId ?? null });

function hasPermission(module: string, action: string): boolean {
    return form.permissions[module]?.includes(action) ?? false;
}

function togglePermission(module: string, action: string, checked: boolean): void {
    const current = form.permissions[module] ?? [];
    form.permissions[module] = checked ? [...current, action] : current.filter((v) => v !== action);
}

function submit() {
    form.patch(props.action, { preserveScroll: true });
}
</script>

<template>
    <form class="space-y-4" @submit.prevent="submit">
        <div class="overflow-x-auto rounded-lg border border-border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-border bg-muted/50">
                        <th class="px-4 py-3 text-left font-semibold text-foreground">Permiso</th>
                        <th v-for="action in actions" :key="action.key" class="w-24 px-2 py-3 text-center font-semibold text-foreground">
                            <div class="flex flex-col items-center gap-1">
                                <component :is="action.icon" class="h-4 w-4" />
                                <span class="text-xs">{{ action.label }}</span>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr
                        v-for="module in modules"
                        :key="module.module_key"
                        class="transition-colors hover:bg-muted/30"
                    >
                        <td class="px-4 py-3">
                            <p class="font-medium text-foreground">{{ module.label }}</p>
                            <p v-if="module.description" class="mt-0.5 text-xs text-muted-foreground">{{ module.description }}</p>
                        </td>
                        <td v-for="action in actions" :key="action.key" class="px-2 py-3 text-center">
                            <Checkbox
                                :model-value="hasPermission(module.module_key, action.key)"
                                binary
                                @update:model-value="togglePermission(module.module_key, action.key, Boolean($event))"
                            />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Button :disabled="form.processing" v-ripple>
            <Save class="h-4 w-4" />
            Guardar permisos
        </Button>
    </form>
</template>
```

- [ ] **Step 3: Build and type-check**

```bash
npm run build && npm run typecheck
```

Expected: no errors.

- [ ] **Step 4: Commit**

```bash
git add resources/js/components/domain/User/PermissionGrid.vue app/Http/Controllers/Clinic/UserController.php
git commit -m "feat: PermissionGrid redesigned as 5-column table with module description"
```

---

## Task 6: UserShow — branch-click interaction + grouped data column + Chip roles

**Files:**
- Modify: `resources/js/pages/Clinic/Users/Show.vue`

- [ ] **Step 1: Replace Show.vue**

Replace full file content:

```vue
<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Pencil, Power, RotateCcw, MapPin, Phone, GraduationCap, BadgeCheck, CircleDollarSign, ConciergeBell, Scissors, ShieldCheck, Stethoscope } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import type { Component } from 'vue';
import Chip from 'primevue/chip';
import PermissionGrid from '@/components/domain/User/PermissionGrid.vue';
import UserStatusBadge from '@/components/domain/User/UserStatusBadge.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { roleLabel } from '@/lib/userLabels';
import * as permissionRoutes from '@/actions/App/Http/Controllers/Clinic/UserPermissionController';
import * as userRoutes from '@/actions/App/Http/Controllers/Clinic/UserController';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    user: {
        id: number;
        name: string;
        email: string;
        phone?: string | null;
        avatar?: string | null;
        professional_license?: string | null;
        is_active: boolean;
        branch?: { id: number; name: string } | null;
        roles?: Array<{ id: number; name: string }>;
        permissions?: Array<{ id: number; name: string }>;
        branch_roles?: Array<{ id: number; branch_id: number; role: string; branch?: { id: number; name: string } | null }>;
        user_branch_permissions?: Array<{ branch_id: number; permission: string }>;
    };
    modules: Array<{ module_key: string; label: string; description?: string }>;
    effectivePermissions: Array<{ id: number; name: string }>;
}>();

const clinic = window.location.hostname.split('.')[0];

const roleIcons: Record<string, Component> = {
    clinic_admin: ShieldCheck,
    veterinarian: Stethoscope,
    groomer: Scissors,
    receptionist: ConciergeBell,
    cashier: CircleDollarSign,
};

function roleIcon(role: string): Component {
    return roleIcons[role] ?? BadgeCheck;
}

function initials(name: string) {
    return name
        .split(' ')
        .filter(Boolean)
        .map((word) => word[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();
}

type BranchGroup = { branch: { id: number; name: string }; roles: string[] };

const branchGroups = computed((): BranchGroup[] => {
    const map = new Map<number, BranchGroup>();
    for (const br of props.user.branch_roles ?? []) {
        if (!br.branch) continue;
        const existing = map.get(br.branch_id);
        if (existing) {
            existing.roles.push(br.role);
        } else {
            map.set(br.branch_id, { branch: br.branch, roles: [br.role] });
        }
    }
    return [...map.values()];
});

const primaryBranchId = computed((): number | null =>
    props.user.branch?.id ?? props.user.branch_roles?.[0]?.branch_id ?? null
);

const selectedBranchId = ref<number | null>(primaryBranchId.value);

const selectedBranchPermissions = computed(() => {
    if (!selectedBranchId.value) return props.effectivePermissions;
    return (props.user.user_branch_permissions ?? [])
        .filter((p) => p.branch_id === selectedBranchId.value)
        .map((p, idx) => ({ id: idx, name: p.permission }));
});

const permissionActionUrl = computed(() => {
    const base = permissionRoutes.update({ clinic, user: props.user.id }).url;
    return selectedBranchId.value ? `${base}?branch_id=${selectedBranchId.value}` : base;
});

function deactivate() {
    router.post(userRoutes.deactivate({ clinic, user: props.user.id }).url, {}, { preserveScroll: true });
}

function restore() {
    router.post(userRoutes.restore({ clinic, user: props.user.id }).url, {}, { preserveScroll: true });
}
</script>

<template>
    <Head :title="user.name" />

    <div class="space-y-6">
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <Avatar class="h-14 w-14">
                    <AvatarImage v-if="user.avatar" :src="user.avatar" :alt="user.name" />
                    <AvatarFallback>{{ initials(user.name) }}</AvatarFallback>
                </Avatar>
                <div>
                    <div class="mb-2"><UserStatusBadge :active="user.is_active" /></div>
                    <h1 class="text-2xl font-bold text-foreground">{{ user.name }}</h1>
                    <p class="text-sm text-muted-foreground">{{ user.email }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                <Button variant="outline" as-child>
                    <Link :href="userRoutes.index(clinic).url">
                        <ArrowLeft class="h-4 w-4" />
                        Volver
                    </Link>
                </Button>
                <Button variant="outline" as-child>
                    <Link :href="userRoutes.edit({ clinic, user: user.id }).url">
                        <Pencil class="h-4 w-4" />
                        Editar
                    </Link>
                </Button>
                <Button v-if="user.is_active" variant="destructive" v-ripple @click="deactivate">
                    <Power class="h-4 w-4" />
                    Desactivar
                </Button>
                <Button v-else v-ripple @click="restore">
                    <RotateCcw class="h-4 w-4" />
                    Activar
                </Button>
            </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-[1fr_2fr]">
            <!-- Datos -->
            <Card>
                <CardHeader><CardTitle>Datos</CardTitle></CardHeader>
                <CardContent class="space-y-4 text-sm">
                    <div v-if="user.phone" class="flex items-center gap-2">
                        <Phone class="h-4 w-4 shrink-0 text-muted-foreground" />
                        <span>{{ user.phone }}</span>
                    </div>
                    <div v-if="user.professional_license" class="flex items-center gap-2">
                        <GraduationCap class="h-4 w-4 shrink-0 text-muted-foreground" />
                        <span>{{ user.professional_license }}</span>
                    </div>

                    <div class="space-y-3 pt-1">
                        <button
                            v-for="group in branchGroups"
                            :key="group.branch.id"
                            type="button"
                            class="w-full space-y-2 rounded-lg border px-3 py-2.5 text-left transition-colors hover:border-primary/50"
                            :class="selectedBranchId === group.branch.id ? 'border-primary bg-primary/5' : 'border-border bg-muted/20'"
                            @click="selectedBranchId = group.branch.id"
                        >
                            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                <MapPin class="h-3.5 w-3.5 shrink-0" />
                                <span class="truncate">{{ group.branch.name }}</span>
                            </div>
                            <div class="flex flex-wrap gap-1 pl-5">
                                <Chip
                                    v-for="role in group.roles"
                                    :key="role"
                                    class="!text-xs !py-0.5 !px-2"
                                >
                                    <template #default>
                                        <component :is="roleIcon(role)" class="h-3 w-3 shrink-0" />
                                        <span class="ml-1 text-xs">{{ roleLabel(role) }}</span>
                                    </template>
                                </Chip>
                            </div>
                        </button>
                        <p v-if="branchGroups.length === 0" class="text-muted-foreground">Sin sucursal asignada</p>
                    </div>
                </CardContent>
            </Card>

            <!-- Permisos directos por sucursal -->
            <Card>
                <CardHeader>
                    <CardTitle>
                        Permisos directos
                        <span v-if="selectedBranchId" class="ml-2 text-sm font-normal text-muted-foreground">
                            — {{ branchGroups.find(g => g.branch.id === selectedBranchId)?.branch.name }}
                        </span>
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <PermissionGrid
                        :action="permissionActionUrl"
                        :modules="modules"
                        :permissions="selectedBranchPermissions"
                        :branch-id="selectedBranchId"
                    />
                </CardContent>
            </Card>
        </div>
    </div>
</template>
```

- [ ] **Step 2: Build and type-check**

```bash
npm run build && npm run typecheck
```

Expected: no errors.

- [ ] **Step 3: Commit**

```bash
git add resources/js/pages/Clinic/Users/Show.vue
git commit -m "feat: UserShow branch-click permissions, grouped data with Chip roles and icons"
```

---

## Task 7: Per-branch permissions — migration, model, action, controller update

**Files:**
- Create: `database/migrations/2026_04_25_100000_create_user_branch_permissions_table.php`
- Create: `app/Domain/User/Models/UserBranchPermission.php`
- Create: `app/Domain/User/Actions/SyncBranchPermissionsAction.php`
- Create: `app/Http/Requests/Clinic/SyncBranchPermissionsRequest.php`
- Modify: `app/Http/Controllers/Clinic/UserPermissionController.php`
- Modify: `app/Http/Controllers/Clinic/UserController.php`

- [ ] **Step 1: Create migration**

Create `database/migrations/2026_04_25_100000_create_user_branch_permissions_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_branch_permissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('clinic_branches')->cascadeOnDelete();
            $table->string('permission', 150);
            $table->timestamps();

            $table->unique(['user_id', 'branch_id', 'permission'], 'ubp_unique');
            $table->index(['user_id', 'branch_id'], 'ubp_user_branch');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_branch_permissions');
    }
};
```

- [ ] **Step 2: Run migration**

```bash
php artisan migrate
```

Expected: `user_branch_permissions` table created.

- [ ] **Step 3: Create UserBranchPermission model**

Create `app/Domain/User/Models/UserBranchPermission.php`:

```php
<?php

namespace App\Domain\User\Models;

use App\Domain\Clinic\Models\ClinicBranch;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBranchPermission extends Model
{
    protected $fillable = ['user_id', 'branch_id', 'permission'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(ClinicBranch::class, 'branch_id');
    }
}
```

- [ ] **Step 4: Add relationship to User model**

In `app/Models/User.php`, add after `branchRoles()`:

```php
use App\Domain\User\Models\UserBranchPermission;

public function userBranchPermissions(): HasMany
{
    return $this->hasMany(UserBranchPermission::class);
}
```

Also add `UserBranchPermission` to the import at the top (it's already there for `UserBranchRole`, add alongside).

- [ ] **Step 5: Create SyncBranchPermissionsAction**

Create `app/Domain/User/Actions/SyncBranchPermissionsAction.php`:

```php
<?php

namespace App\Domain\User\Actions;

use App\Domain\Clinic\Models\ClinicModule;
use App\Domain\User\Events\PermissionsChanged;
use App\Domain\User\Models\UserBranchPermission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncBranchPermissionsAction
{
    /**
     * @param  array<string, list<string>>  $permissionsByModule
     */
    public function handle(User $user, int $branchId, array $permissionsByModule): void
    {
        DB::transaction(function () use ($user, $branchId, $permissionsByModule): void {
            $activeModules = ClinicModule::withoutGlobalScopes()
                ->where('clinic_id', $user->clinic_id)
                ->where('is_active', true)
                ->pluck('module_key');

            $permissions = $activeModules
                ->flatMap(fn (string $module) => collect($permissionsByModule[$module] ?? [])
                    ->filter(fn (string $action) => in_array($action, ['view', 'create', 'update', 'delete'], true))
                    ->map(fn (string $action) => "{$module}.{$action}"))
                ->values()
                ->all();

            UserBranchPermission::where('user_id', $user->id)
                ->where('branch_id', $branchId)
                ->delete();

            foreach ($permissions as $permission) {
                UserBranchPermission::create([
                    'user_id' => $user->id,
                    'branch_id' => $branchId,
                    'permission' => $permission,
                ]);
            }

            PermissionsChanged::dispatch($user, auth()->user());

            Log::channel('security')->info('user_branch_permissions_synced', [
                'clinic_id' => $user->clinic_id,
                'user_id' => $user->id,
                'branch_id' => $branchId,
                'by_user_id' => auth()->id(),
                'permissions' => $permissions,
            ]);
        });
    }
}
```

- [ ] **Step 6: Create SyncBranchPermissionsRequest**

Create `app/Http/Requests/Clinic/SyncBranchPermissionsRequest.php`:

```php
<?php

namespace App\Http\Requests\Clinic;

use App\Domain\Clinic\Models\ClinicBranch;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class SyncBranchPermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user instanceof User && $this->user()?->can('managePermissions', $user) === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'permissions' => ['required', 'array'],
            'permissions.*' => ['array'],
            'permissions.*.*' => ['string', 'in:view,create,update,delete'],
            'branch_id' => [
                'nullable',
                'integer',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value !== null && ! ClinicBranch::withoutGlobalScopes()->where('clinic_id', current_clinic()->id)->where('id', $value)->exists()) {
                        $fail('La sucursal no pertenece a esta clínica.');
                    }
                },
            ],
        ];
    }
}
```

- [ ] **Step 7: Update UserPermissionController**

Replace `app/Http/Controllers/Clinic/UserPermissionController.php`:

```php
<?php

namespace App\Http\Controllers\Clinic;

use App\Domain\User\Actions\SyncBranchPermissionsAction;
use App\Domain\User\Actions\SyncPermissionsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Clinic\SyncBranchPermissionsRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class UserPermissionController extends Controller
{
    public function update(
        SyncBranchPermissionsRequest $request,
        string $clinic,
        User $user,
        SyncPermissionsAction $globalAction,
        SyncBranchPermissionsAction $branchAction,
    ): RedirectResponse {
        $branchId = $request->integer('branch_id') ?: null;

        if ($branchId !== null) {
            $branchAction->handle($user, $branchId, $request->validated('permissions'));
        } else {
            $globalAction->handle($user, $request->validated('permissions'));
        }

        return back()->with('success', 'Permisos actualizados.');
    }
}
```

- [ ] **Step 8: Update UserController::show to pass branch permissions**

In `app/Http/Controllers/Clinic/UserController.php`, replace `show()`:

```php
public function show(string $clinic, User $user): Response
{
    $this->authorize('view', $user);

    $loaded = $user->load([
        'branch',
        'roles',
        'permissions',
        'branchRoles.branch',
        'userBranchPermissions',
    ]);

    $userBranchPermissions = $loaded->userBranchPermissions
        ->map(fn ($p) => ['branch_id' => $p->branch_id, 'permission' => $p->permission])
        ->values()
        ->all();

    return Inertia::render('Clinic/Users/Show', [
        'user' => array_merge(
            $loaded->toArray(),
            ['user_branch_permissions' => $userBranchPermissions]
        ),
        'effectivePermissions' => $user->getAllPermissions()->values(),
        ...$this->formProps(),
    ]);
}
```

- [ ] **Step 9: Write feature test**

Create `tests/Feature/Clinic/BranchPermissionsTest.php`:

```php
<?php

use App\Domain\User\Models\UserBranchPermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('syncs branch permissions for a user', function () {
    // Arrange
    $admin = User::factory()->clinicAdmin()->create();
    $target = User::factory()->forClinic($admin->clinic_id)->create();
    $branch = $admin->branch;

    // Act
    $this->actingAs($admin)
        ->patch(route('clinic.users.permissions.update', [
            'clinic' => $admin->clinic->slug,
            'user' => $target->id,
        ]), [
            'branch_id' => $branch->id,
            'permissions' => ['patients' => ['view', 'create']],
        ])
        ->assertRedirect();

    // Assert
    expect(UserBranchPermission::where('user_id', $target->id)
        ->where('branch_id', $branch->id)
        ->count())->toBe(2);
});
```

- [ ] **Step 10: Run tests**

```bash
php artisan test --parallel --filter=BranchPermissionsTest
```

Expected: 1 test passes.

- [ ] **Step 11: Commit**

```bash
git add database/migrations/2026_04_25_100000_create_user_branch_permissions_table.php \
    app/Domain/User/Models/UserBranchPermission.php \
    app/Domain/User/Actions/SyncBranchPermissionsAction.php \
    app/Http/Requests/Clinic/SyncBranchPermissionsRequest.php \
    app/Http/Controllers/Clinic/UserPermissionController.php \
    app/Http/Controllers/Clinic/UserController.php \
    app/Models/User.php \
    tests/Feature/Clinic/BranchPermissionsTest.php
git commit -m "feat: per-branch direct permissions (user_branch_permissions table + action + controller)"
```

---

## Task 8: Clinic-role-module configuration (superadmin)

**Files:**
- Create: `database/migrations/2026_04_25_100001_create_clinic_role_modules_table.php`
- Create: `app/Domain/Clinic/Models/ClinicRoleModule.php`
- Create: `app/Domain/Clinic/Actions/SyncClinicRoleModulesAction.php`
- Create: `app/Http/Controllers/Admin/ClinicRoleModuleController.php`
- Create: `app/Http/Requests/Admin/SyncClinicRoleModulesRequest.php`
- Modify: `routes/admin.php`
- Modify: `resources/js/pages/Admin/Clinics/Show.vue` — add "Roles y módulos" tab

- [ ] **Step 1: Create migration**

Create `database/migrations/2026_04_25_100001_create_clinic_role_modules_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinic_role_modules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->string('role', 80);
            $table->string('module_key', 100);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->unique(['clinic_id', 'role', 'module_key'], 'crm_unique');
            $table->index(['clinic_id', 'role'], 'crm_clinic_role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_role_modules');
    }
};
```

- [ ] **Step 2: Run migration**

```bash
php artisan migrate
```

Expected: `clinic_role_modules` table created.

- [ ] **Step 3: Create ClinicRoleModule model**

Create `app/Domain/Clinic/Models/ClinicRoleModule.php`:

```php
<?php

namespace App\Domain\Clinic\Models;

use App\Domain\User\Enums\UserRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicRoleModule extends Model
{
    protected $fillable = ['clinic_id', 'role', 'module_key', 'is_enabled'];

    protected function casts(): array
    {
        return ['is_enabled' => 'boolean'];
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    /**
     * Returns enabled module_keys for a given clinic+role.
     * If no rows exist, returns all active modules (open-world default).
     *
     * @return list<string>
     */
    public static function enabledModulesForRole(int $clinicId, string $role): array
    {
        $rows = self::where('clinic_id', $clinicId)->where('role', $role)->get();

        if ($rows->isEmpty()) {
            return ClinicModule::withoutGlobalScopes()
                ->where('clinic_id', $clinicId)
                ->where('is_active', true)
                ->pluck('module_key')
                ->all();
        }

        return $rows->where('is_enabled', true)->pluck('module_key')->all();
    }
}
```

- [ ] **Step 4: Create SyncClinicRoleModulesAction**

Create `app/Domain/Clinic/Actions/SyncClinicRoleModulesAction.php`:

```php
<?php

namespace App\Domain\Clinic\Actions;

use App\Domain\Clinic\Enums\ModuleKey;
use App\Domain\Clinic\Models\Clinic;
use App\Domain\Clinic\Models\ClinicRoleModule;
use Illuminate\Support\Facades\DB;

class SyncClinicRoleModulesAction
{
    /**
     * @param  list<string>  $enabledModuleKeys
     */
    public function handle(Clinic $clinic, string $role, array $enabledModuleKeys): void
    {
        DB::transaction(function () use ($clinic, $role, $enabledModuleKeys): void {
            ClinicRoleModule::where('clinic_id', $clinic->id)->where('role', $role)->delete();

            foreach (ModuleKey::cases() as $module) {
                ClinicRoleModule::create([
                    'clinic_id' => $clinic->id,
                    'role' => $role,
                    'module_key' => $module->value,
                    'is_enabled' => in_array($module->value, $enabledModuleKeys, true),
                ]);
            }
        });
    }
}
```

- [ ] **Step 5: Create SyncClinicRoleModulesRequest**

Create `app/Http/Requests/Admin/SyncClinicRoleModulesRequest.php`:

```php
<?php

namespace App\Http\Requests\Admin;

use App\Domain\Clinic\Enums\ModuleKey;
use App\Domain\User\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncClinicRoleModulesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_super_admin;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'role' => ['required', Rule::enum(UserRole::class)],
            'enabled_modules' => ['required', 'array'],
            'enabled_modules.*' => [Rule::enum(ModuleKey::class)],
        ];
    }
}
```

- [ ] **Step 6: Create ClinicRoleModuleController**

Create `app/Http/Controllers/Admin/ClinicRoleModuleController.php`:

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Clinic\Actions\SyncClinicRoleModulesAction;
use App\Domain\Clinic\Enums\ModuleKey;
use App\Domain\Clinic\Models\Clinic;
use App\Domain\Clinic\Models\ClinicRoleModule;
use App\Domain\User\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SyncClinicRoleModulesRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClinicRoleModuleController extends Controller
{
    public function show(Request $request, Clinic $clinic, string $role): Response
    {
        abort_unless((bool) $request->user()?->is_super_admin, 403);
        UserRole::from($role);

        $modules = collect(ModuleKey::cases())->map(fn (ModuleKey $module) => [
            'key' => $module->value,
            'label' => $module->label(),
            'description' => $module->description(),
            'icon' => $module->icon(),
            'is_enabled' => in_array($module->value, ClinicRoleModule::enabledModulesForRole($clinic->id, $role)),
        ]);

        return Inertia::render('Admin/Clinics/RoleModules', [
            'clinic' => $clinic->only('id', 'commercial_name'),
            'role' => $role,
            'roleLabel' => UserRole::from($role)->label(),
            'modules' => $modules,
        ]);
    }

    public function update(SyncClinicRoleModulesRequest $request, Clinic $clinic, SyncClinicRoleModulesAction $action): RedirectResponse
    {
        $action->handle($clinic, $request->validated('role'), $request->validated('enabled_modules'));

        return back()->with('success', 'Configuración de módulos actualizada.');
    }
}
```

- [ ] **Step 7: Add routes to admin.php**

In `routes/admin.php`, inside the `clinics` prefix group (after the last existing route):

```php
use App\Http\Controllers\Admin\ClinicRoleModuleController;

// inside Route::prefix('clinics')->name('admin.clinics.')->group(...)
Route::get('/{clinic}/role-modules/{role}', [ClinicRoleModuleController::class, 'show'])->name('role-modules.show');
Route::put('/{clinic}/role-modules', [ClinicRoleModuleController::class, 'update'])->name('role-modules.update');
```

Also add the import at the top of `routes/admin.php`.

- [ ] **Step 8: Add "Roles y Módulos" tab to Admin/Clinics/Show.vue**

In `resources/js/pages/Admin/Clinics/Show.vue`, update the `<Tabs>` component:

1. Change `grid-cols-4` to `grid-cols-5` in TabsList.
2. Add the new tab trigger after the existing 4:

```vue
<TabsList class="grid w-full grid-cols-5">
    <TabsTrigger value="general">General</TabsTrigger>
    <TabsTrigger value="branches">Sucursales</TabsTrigger>
    <TabsTrigger value="modules">Módulos</TabsTrigger>
    <TabsTrigger value="users">Usuarios</TabsTrigger>
    <TabsTrigger value="role-modules">Roles y módulos</TabsTrigger>
</TabsList>
```

3. Add the new tab content (after the Users tab content):

```vue
<!-- Role Modules -->
<TabsContent value="role-modules">
    <Card>
        <CardHeader>
            <CardTitle>Acceso por rol</CardTitle>
            <p class="text-sm text-muted-foreground">
                Configura qué módulos puede ver cada rol en esta clínica.
                Los cambios aplican al acceso del rol para todos sus usuarios.
            </p>
        </CardHeader>
        <CardContent class="space-y-6">
            <div
                v-for="role in availableRoles"
                :key="role.value"
                class="space-y-3"
            >
                <h3 class="flex items-center gap-2 text-sm font-semibold text-foreground">
                    <component :is="roleIcons[role.value] ?? BadgeCheck" class="h-4 w-4 text-muted-foreground" />
                    {{ role.label }}
                </h3>
                <div class="grid grid-cols-2 gap-2 md:grid-cols-3 2xl:grid-cols-4">
                    <label
                        v-for="module in props.modules"
                        :key="`${role.value}-${module.key}`"
                        class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition-colors hover:border-primary/50"
                        :class="isRoleModuleEnabled(role.value, module.key) ? 'border-primary bg-primary/5' : 'border-border'"
                    >
                        <Checkbox
                            :model-value="isRoleModuleEnabled(role.value, module.key)"
                            binary
                            @update:model-value="toggleRoleModule(role.value, module.key, Boolean($event))"
                        />
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-foreground">{{ module.label }}</p>
                        </div>
                    </label>
                </div>
            </div>

            <Button v-ripple @click="saveRoleModules">
                <Save class="h-4 w-4" />
                Guardar configuración
            </Button>
        </CardContent>
    </Card>
</TabsContent>
```

4. Add the necessary imports and script logic in the `<script setup>` section:

```ts
import Checkbox from 'primevue/checkbox';
import { Save, BadgeCheck, CircleDollarSign, ConciergeBell, Scissors, ShieldCheck, Stethoscope } from 'lucide-vue-next';
import type { Component } from 'vue';
import * as roleModuleRoutes from '@/actions/App/Http/Controllers/Admin/ClinicRoleModuleController';

// Add to props definition:
// modules already exists; add roleModuleConfig to clinic prop:
// roleModuleConfig: Array<{role: string; module_key: string; is_enabled: boolean}>

const roleIcons: Record<string, Component> = {
    clinic_admin: ShieldCheck,
    veterinarian: Stethoscope,
    groomer: Scissors,
    receptionist: ConciergeBell,
    cashier: CircleDollarSign,
};

const availableRoles = [
    { value: 'clinic_admin', label: 'Administrador de clínica' },
    { value: 'veterinarian', label: 'Veterinario' },
    { value: 'groomer', label: 'Esteticista' },
    { value: 'receptionist', label: 'Recepcionista' },
    { value: 'cashier', label: 'Cajero' },
];

// Local reactive state for role-module toggles
const roleModuleState = ref<Record<string, Record<string, boolean>>>({});

function initRoleModuleState() {
    for (const role of availableRoles) {
        roleModuleState.value[role.value] = {};
        for (const module of props.modules) {
            const row = (props.clinic as any).roleModuleConfig?.find(
                (r: any) => r.role === role.value && r.module_key === module.key
            );
            roleModuleState.value[role.value][module.key] = row ? row.is_enabled : true;
        }
    }
}

onMounted(() => initRoleModuleState());

function isRoleModuleEnabled(role: string, moduleKey: string): boolean {
    return roleModuleState.value[role]?.[moduleKey] ?? true;
}

function toggleRoleModule(role: string, moduleKey: string, enabled: boolean) {
    if (!roleModuleState.value[role]) roleModuleState.value[role] = {};
    roleModuleState.value[role][moduleKey] = enabled;
}

function saveRoleModules() {
    for (const role of availableRoles) {
        const enabledModules = Object.entries(roleModuleState.value[role] ?? {})
            .filter(([, enabled]) => enabled)
            .map(([key]) => key);

        router.put(
            roleModuleRoutes.update({ clinic: props.clinic.id }).url,
            { role: role.value, enabled_modules: enabledModules },
            { preserveScroll: true }
        );
    }
}
```

5. Update `ClinicController::show()` to pass `roleModuleConfig` in clinic data. In `app/Http/Controllers/Admin/ClinicController.php`, in the `show()` method, add to the clinic prop:

```php
// After loading clinic with relations, add:
$roleModuleConfig = \App\Domain\Clinic\Models\ClinicRoleModule::where('clinic_id', $clinic->id)
    ->get(['role', 'module_key', 'is_enabled'])
    ->toArray();

// Pass alongside existing clinic data:
'roleModuleConfig' => $roleModuleConfig,
```

Also regenerate Wayfinder for the new controller:
```bash
php artisan wayfinder:generate
```

- [ ] **Step 9: Write feature test**

Create `tests/Feature/Admin/ClinicRoleModuleTest.php`:

```php
<?php

use App\Domain\Clinic\Models\ClinicRoleModule;
use App\Domain\Clinic\Enums\ModuleKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('superadmin can configure role modules for a clinic', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $clinic = \App\Domain\Clinic\Models\Clinic::factory()->create();

    $this->actingAs($superAdmin)
        ->put(route('admin.clinics.role-modules.update', $clinic), [
            'role' => 'veterinarian',
            'enabled_modules' => ['patients', 'appointments'],
        ])
        ->assertRedirect();

    expect(ClinicRoleModule::where('clinic_id', $clinic->id)
        ->where('role', 'veterinarian')
        ->where('module_key', 'patients')
        ->value('is_enabled'))->toBeTrue();

    expect(ClinicRoleModule::where('clinic_id', $clinic->id)
        ->where('role', 'veterinarian')
        ->where('module_key', 'inventory')
        ->value('is_enabled'))->toBeFalse();
});
```

- [ ] **Step 10: Run tests**

```bash
php artisan test --parallel --filter=ClinicRoleModuleTest
```

Expected: 1 test passes.

- [ ] **Step 11: Build and type-check**

```bash
npm run build && npm run typecheck
```

Expected: no errors.

- [ ] **Step 12: Commit**

```bash
git add database/migrations/2026_04_25_100001_create_clinic_role_modules_table.php \
    app/Domain/Clinic/Models/ClinicRoleModule.php \
    app/Domain/Clinic/Actions/SyncClinicRoleModulesAction.php \
    app/Http/Controllers/Admin/ClinicRoleModuleController.php \
    app/Http/Requests/Admin/SyncClinicRoleModulesRequest.php \
    routes/admin.php \
    resources/js/pages/Admin/Clinics/Show.vue \
    app/Http/Controllers/Admin/ClinicController.php \
    resources/js/actions \
    tests/Feature/Admin/ClinicRoleModuleTest.php
git commit -m "feat: clinic-role-module configuration for superadmin (clinic_role_modules table + UI tab)"
```

---

## Task 9: Superadmin clinic user management (Admin/Clinics/Show — Users tab)

**Files:**
- Create: `app/Http/Controllers/Admin/ClinicUserController.php`
- Create: `app/Http/Requests/Admin/UpdateClinicUserRequest.php`
- Modify: `routes/admin.php`
- Modify: `resources/js/pages/Admin/Clinics/Show.vue` — expand Users tab

- [ ] **Step 1: Create UpdateClinicUserRequest**

Create `app/Http/Requests/Admin/UpdateClinicUserRequest.php`:

```php
<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClinicUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_super_admin;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name' => ['required', 'string', 'max:200'],
            'email' => ['required', 'email', 'max:200', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:20'],
        ];
    }
}
```

- [ ] **Step 2: Create ClinicUserController**

Create `app/Http/Controllers/Admin/ClinicUserController.php`:

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Clinic\Models\Clinic;
use App\Domain\User\Actions\DeactivateUserAction;
use App\Domain\User\Actions\RestoreUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateClinicUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClinicUserController extends Controller
{
    public function update(UpdateClinicUserRequest $request, Clinic $clinic, User $user): RedirectResponse
    {
        abort_unless($user->clinic_id === $clinic->id, 403);

        $user->update($request->only('name', 'email', 'phone'));

        return back()->with('success', "Usuario {$user->name} actualizado.");
    }

    public function activate(Request $request, Clinic $clinic, User $user, RestoreUserAction $action): RedirectResponse
    {
        abort_unless((bool) $request->user()?->is_super_admin, 403);
        abort_unless($user->clinic_id === $clinic->id, 403);

        $action->handle($user);

        return back()->with('success', "{$user->name} activado.");
    }

    public function deactivate(Request $request, Clinic $clinic, User $user, DeactivateUserAction $action): RedirectResponse
    {
        abort_unless((bool) $request->user()?->is_super_admin, 403);
        abort_unless($user->clinic_id === $clinic->id, 403);

        $action->handle($user);

        return back()->with('success', "{$user->name} suspendido.");
    }

    public function destroy(Request $request, Clinic $clinic, User $user): RedirectResponse
    {
        abort_unless((bool) $request->user()?->is_super_admin, 403);
        abort_unless($user->clinic_id === $clinic->id, 403);
        abort_if($user->is_super_admin, 403);

        $user->delete();

        return back()->with('success', "{$user->name} eliminado.");
    }
}
```

- [ ] **Step 3: Add routes**

In `routes/admin.php`, inside the clinics group, add after existing user routes:

```php
use App\Http\Controllers\Admin\ClinicUserController;

// inside Route::prefix('clinics')->name('admin.clinics.')->group(...)
Route::put('/{clinic}/users/{user}', [ClinicUserController::class, 'update'])->name('users.update');
Route::post('/{clinic}/users/{user}/activate', [ClinicUserController::class, 'activate'])->name('users.activate');
Route::post('/{clinic}/users/{user}/deactivate', [ClinicUserController::class, 'deactivate'])->name('users.deactivate');
Route::delete('/{clinic}/users/{user}', [ClinicUserController::class, 'destroy'])->name('users.destroy');
```

- [ ] **Step 4: Regenerate Wayfinder**

```bash
php artisan wayfinder:generate
```

- [ ] **Step 5: Expand Users tab in Admin/Clinics/Show.vue**

The existing Users tab (`<TabsContent value="users">`) shows a simple user list. Replace the users tab content. 

First, update the `users` prop type in `defineProps` to include more fields:

```ts
users: Array<{
    id: number;
    name: string;
    email: string;
    phone?: string | null;
    is_active: boolean;
    email_verified_at: string | null;
    roles?: Array<{ name: string }>;
}>;
```

Update `ClinicController::show()` to eager-load roles on users:

```php
// In the show() return, change:
'users' => $clinic->users()->with('roles:id,name')->get([
    'id', 'name', 'email', 'phone', 'is_active', 'email_verified_at'
]),
```

Update the Users tab in `Admin/Clinics/Show.vue`:

```vue
<TabsContent value="users">
    <Card>
        <CardHeader>
            <div class="flex items-center justify-between">
                <CardTitle>Usuarios</CardTitle>
                <Button size="sm" @click="showInviteForm = !showInviteForm">
                    <UserPlus class="h-4 w-4" />
                    Invitar usuario
                </Button>
            </div>
        </CardHeader>
        <CardContent class="space-y-4">
            <!-- Invite form (existing) -->
            <div v-if="showInviteForm" class="space-y-3 rounded-lg border border-primary/30 bg-primary/5 p-4">
                <p class="text-sm font-medium text-foreground">Nuevo usuario</p>
                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-2">
                        <Label>Nombre <span class="text-destructive">*</span></Label>
                        <Input v-model="inviteForm.name" placeholder="María García" />
                        <p v-if="inviteForm.errors.name" class="text-xs text-destructive">{{ inviteForm.errors.name }}</p>
                    </div>
                    <div class="space-y-2">
                        <Label>Email <span class="text-destructive">*</span></Label>
                        <Input v-model="inviteForm.email" type="email" placeholder="usuario@clinica.com" />
                        <p v-if="inviteForm.errors.email" class="text-xs text-destructive">{{ inviteForm.errors.email }}</p>
                    </div>
                </div>
                <div class="space-y-2">
                    <Label>Teléfono</Label>
                    <Input v-model="inviteForm.phone" type="tel" placeholder="+52 55 1234 5678" />
                </div>
                <div class="flex gap-2">
                    <Button size="sm" :disabled="inviteForm.processing" @click="inviteUser" v-ripple>
                        <UserPlus class="h-3.5 w-3.5" />
                        Enviar invitación
                    </Button>
                    <Button size="sm" variant="ghost" @click="showInviteForm = false">Cancelar</Button>
                </div>
            </div>

            <!-- User list -->
            <div v-if="props.clinic.users.length === 0 && !showInviteForm" class="py-8 text-center text-muted-foreground">
                Sin usuarios registrados en esta clínica.
            </div>
            <div v-else class="divide-y divide-border">
                <div v-for="user in props.clinic.users" :key="user.id" class="py-3">
                    <!-- View mode -->
                    <div v-if="editingUserId !== user.id" class="flex items-center justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-foreground">{{ user.name }}</p>
                            <p class="text-sm text-muted-foreground">{{ user.email }}</p>
                            <div class="mt-1 flex flex-wrap gap-1">
                                <Chip
                                    v-for="role in (user.roles ?? [])"
                                    :key="role.name"
                                    class="!text-xs !py-0.5 !px-2"
                                >
                                    <template #default>
                                        <component :is="roleIcons[role.name] ?? BadgeCheck" class="h-3 w-3 shrink-0" />
                                        <span class="ml-1 text-xs">{{ roleLabel(role.name) }}</span>
                                    </template>
                                </Chip>
                            </div>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <Badge v-if="user.email_verified_at" variant="outline" class="gap-1 text-xs text-success">
                                <ShieldCheck class="h-3 w-3" />
                                Verificado
                            </Badge>
                            <Badge v-else variant="outline" class="gap-1 text-xs text-warning">
                                <AlertCircle class="h-3 w-3" />
                                Sin verificar
                            </Badge>
                            <Badge :variant="user.is_active ? 'default' : 'secondary'" class="text-xs">
                                {{ user.is_active ? 'Activo' : 'Suspendido' }}
                            </Badge>
                            <Button size="sm" variant="ghost" @click="editingUserId = user.id" title="Editar">
                                <Pencil class="h-3.5 w-3.5" />
                            </Button>
                            <Button
                                v-if="isSuperAdmin && !user.email_verified_at"
                                size="sm"
                                variant="outline"
                                class="text-xs"
                                @click="verifyEmail(user.id)"
                                v-ripple
                            >
                                <ShieldCheck class="h-3.5 w-3.5" />
                                Verificar
                            </Button>
                            <Button
                                v-if="user.is_active"
                                size="sm"
                                variant="outline"
                                class="text-xs text-warning"
                                @click="suspendUser(user.id)"
                                v-ripple
                            >
                                <Power class="h-3.5 w-3.5" />
                                Suspender
                            </Button>
                            <Button
                                v-else
                                size="sm"
                                variant="outline"
                                class="text-xs text-success"
                                @click="activateUser(user.id)"
                                v-ripple
                            >
                                <RotateCcw class="h-3.5 w-3.5" />
                                Activar
                            </Button>
                            <Button
                                size="sm"
                                variant="ghost"
                                class="text-destructive hover:text-destructive"
                                @click="deleteUser(user.id, user.name)"
                                v-ripple
                            >
                                <Trash2 class="h-3.5 w-3.5" />
                            </Button>
                        </div>
                    </div>

                    <!-- Edit mode (inline) -->
                    <div v-else class="space-y-3 rounded-lg border border-primary/30 bg-primary/5 p-3">
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <Label class="text-xs">Nombre</Label>
                                <Input v-model="editForms[user.id].name" class="mt-1 h-8 text-sm" />
                            </div>
                            <div>
                                <Label class="text-xs">Email</Label>
                                <Input v-model="editForms[user.id].email" type="email" class="mt-1 h-8 text-sm" />
                            </div>
                            <div>
                                <Label class="text-xs">Teléfono</Label>
                                <Input v-model="editForms[user.id].phone" class="mt-1 h-8 text-sm" />
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <Button size="sm" @click="saveUser(user.id)" v-ripple>
                                <Save class="h-3.5 w-3.5" />
                                Guardar
                            </Button>
                            <Button size="sm" variant="ghost" @click="editingUserId = null">Cancelar</Button>
                        </div>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</TabsContent>
```

Add the additional script logic:

```ts
import { Power, RotateCcw, Trash2, Pencil, Save } from 'lucide-vue-next';
import Chip from 'primevue/chip';
import { roleLabel } from '@/lib/userLabels';
import * as clinicUserRoutes from '@/actions/App/Http/Controllers/Admin/ClinicUserController';

const editingUserId = ref<number | null>(null);

type EditForm = { name: string; email: string; phone: string };
const editForms = ref<Record<number, EditForm>>({});

watch(editingUserId, (id) => {
    if (id !== null) {
        const user = props.clinic.users.find((u) => u.id === id);
        if (user) {
            editForms.value[id] = { name: user.name, email: user.email, phone: user.phone ?? '' };
        }
    }
});

function saveUser(userId: number) {
    router.put(
        clinicUserRoutes.update({ clinic: props.clinic.id, user: userId }).url,
        editForms.value[userId],
        {
            preserveScroll: true,
            onSuccess: () => { editingUserId.value = null; },
        }
    );
}

function suspendUser(userId: number) {
    router.post(
        clinicUserRoutes.deactivate({ clinic: props.clinic.id, user: userId }).url,
        {},
        { preserveScroll: true }
    );
}

function activateUser(userId: number) {
    router.post(
        clinicUserRoutes.activate({ clinic: props.clinic.id, user: userId }).url,
        {},
        { preserveScroll: true }
    );
}

function deleteUser(userId: number, name: string) {
    if (!confirm(`¿Eliminar a ${name}? Esta acción no se puede deshacer.`)) return;
    router.delete(
        clinicUserRoutes.destroy({ clinic: props.clinic.id, user: userId }).url,
        { preserveScroll: true }
    );
}
```

- [ ] **Step 6: Write feature test**

Create `tests/Feature/Admin/ClinicUserManagementTest.php`:

```php
<?php

use App\Models\User;
use App\Domain\Clinic\Models\Clinic;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('superadmin can deactivate a clinic user', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $clinic = Clinic::factory()->create();
    $clinicUser = User::factory()->forClinic($clinic->id)->create(['is_active' => true]);

    $this->actingAs($superAdmin)
        ->post(route('admin.clinics.users.deactivate', [
            'clinic' => $clinic->id,
            'user' => $clinicUser->id,
        ]))
        ->assertRedirect();

    expect($clinicUser->fresh()->is_active)->toBeFalse();
});

it('superadmin can update clinic user data', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $clinic = Clinic::factory()->create();
    $clinicUser = User::factory()->forClinic($clinic->id)->create();

    $this->actingAs($superAdmin)
        ->put(route('admin.clinics.users.update', [
            'clinic' => $clinic->id,
            'user' => $clinicUser->id,
        ]), [
            'name' => 'Nombre Actualizado',
            'email' => $clinicUser->email,
            'phone' => '+52 55 0000 0000',
        ])
        ->assertRedirect();

    expect($clinicUser->fresh()->name)->toBe('Nombre Actualizado');
});
```

- [ ] **Step 7: Run tests**

```bash
php artisan test --parallel --filter=ClinicUserManagementTest
```

Expected: both tests pass.

- [ ] **Step 8: Build and type-check**

```bash
npm run build && npm run typecheck
```

Expected: no errors.

- [ ] **Step 9: Commit**

```bash
git add app/Http/Controllers/Admin/ClinicUserController.php \
    app/Http/Requests/Admin/UpdateClinicUserRequest.php \
    routes/admin.php \
    resources/js/pages/Admin/Clinics/Show.vue \
    resources/js/actions \
    tests/Feature/Admin/ClinicUserManagementTest.php
git commit -m "feat: superadmin clinic user management (edit, activate, suspend, delete)"
```

---

## Task 10: Sidebar permission filtering + share auth.permissions

**Files:**
- Modify: `resources/js/types/auth.ts`
- Modify: `app/Http/Middleware/HandleInertiaRequests.php`
- Modify: `resources/js/components/AppSidebar.vue`

- [ ] **Step 1: Update Auth TypeScript types**

Replace `resources/js/types/auth.ts`:

```ts
export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string | null;
    avatar_path?: string | null;
    email_verified_at: string | null;
    is_super_admin?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
    permissions: string[];
};

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
```

- [ ] **Step 2: Share permissions in HandleInertiaRequests**

In `app/Http/Middleware/HandleInertiaRequests.php`, update `share()`:

```php
public function share(Request $request): array
{
    $user = $request->user();

    return [
        ...parent::share($request),
        'name' => config('app.name'),
        'branding' => [
            'apexDomain' => config('branding.apex_domain'),
            'publicSubdomain' => config('branding.public_subdomain'),
            'superadminSubdomain' => config('branding.superadmin_subdomain'),
            'portalSubdomain' => config('branding.portal_subdomain'),
            'scheme' => config('branding.scheme'),
        ],
        'auth' => [
            'user' => $user,
            'permissions' => $user
                ? $user->getAllPermissions()->pluck('name')->values()->all()
                : [],
        ],
        'context' => $this->resolveContext($request),
        'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
    ];
}
```

- [ ] **Step 3: Update AppSidebar.vue to filter nav items by permission**

In `resources/js/components/AppSidebar.vue`, replace the `<script setup>` and add permission filtering:

```vue
<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutGrid, LayoutDashboard, Building2, Sun, Moon, Monitor, Users } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarSeparator,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import * as adminDashboard from '@/actions/App/Http/Controllers/Admin/AdminDashboardController';
import * as clinicRoutes from '@/actions/App/Http/Controllers/Admin/ClinicController';
import * as clinicUserRoutes from '@/actions/App/Http/Controllers/Clinic/UserController';
import { useAppearance } from '@/composables/useAppearance';
import type { NavItem, SharedPageProps } from '@/types';

const page = usePage<SharedPageProps>();
const { appearance, updateAppearance } = useAppearance();
const clinic = window.location.hostname.split('.')[0];

const context = computed(() => page.props.context ?? 'app');
const authUser = computed(() => page.props.auth?.user);
const permissions = computed((): string[] => (page.props.auth as any)?.permissions ?? []);
const isSuperAdmin = computed(() => authUser.value?.is_super_admin === true);

const logoHref = computed(() =>
    context.value === 'admin' ? adminDashboard.index().url : dashboard(),
);

function canSee(permission?: string): boolean {
    if (!permission) return true;
    if (isSuperAdmin.value) return true;
    if (context.value !== 'clinic') return true;
    return permissions.value.includes(permission);
}

type NavItemWithPermission = NavItem & { permission?: string };

const navItems = computed<NavItemWithPermission[]>(() => {
    if (context.value === 'admin') {
        return [
            { title: 'Panel de administración', href: adminDashboard.index().url, icon: LayoutDashboard },
            { title: 'Clínicas', href: clinicRoutes.index().url, icon: Building2 },
        ];
    }
    if (context.value === 'clinic') {
        return [
            { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
            { title: 'Usuarios', href: clinicUserRoutes.index(clinic).url, icon: Users, permission: 'users.view' },
        ];
    }

    return [
        { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
    ];
});

const visibleNavItems = computed(() => navItems.value.filter((item) => canSee(item.permission)));
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="logoHref">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="visibleNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <SidebarMenu>
                <SidebarMenuItem>
                    <div class="flex items-center justify-between gap-1 px-2 py-1">
                        <span class="text-xs text-muted-foreground group-data-[collapsible=icon]:hidden">Tema</span>
                        <div class="flex gap-0.5 group-data-[collapsible=icon]:flex-col">
                            <button
                                :class="['rounded p-1 transition-colors', appearance === 'light' ? 'bg-sidebar-accent text-sidebar-accent-foreground' : 'text-muted-foreground hover:text-foreground']"
                                title="Tema claro"
                                @click="updateAppearance('light')"
                            >
                                <Sun class="h-3.5 w-3.5" />
                            </button>
                            <button
                                :class="['rounded p-1 transition-colors', appearance === 'dark' ? 'bg-sidebar-accent text-sidebar-accent-foreground' : 'text-muted-foreground hover:text-foreground']"
                                title="Tema oscuro"
                                @click="updateAppearance('dark')"
                            >
                                <Moon class="h-3.5 w-3.5" />
                            </button>
                            <button
                                :class="['rounded p-1 transition-colors', appearance === 'system' ? 'bg-sidebar-accent text-sidebar-accent-foreground' : 'text-muted-foreground hover:text-foreground']"
                                title="Tema del sistema"
                                @click="updateAppearance('system')"
                            >
                                <Monitor class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    </div>
                </SidebarMenuItem>
            </SidebarMenu>
            <SidebarSeparator />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
```

- [ ] **Step 4: Build and type-check**

```bash
npm run build && npm run typecheck
```

Expected: no errors.

- [ ] **Step 5: Run full test suite**

```bash
php artisan test --parallel
```

Expected: all tests pass (or previously-passing tests still pass).

- [ ] **Step 6: Run Pint and PHPStan**

```bash
vendor/bin/pint --test
vendor/bin/phpstan analyse --memory-limit=1G
```

Expected: no issues.

- [ ] **Step 7: Commit**

```bash
git add resources/js/types/auth.ts \
    app/Http/Middleware/HandleInertiaRequests.php \
    resources/js/components/AppSidebar.vue
git commit -m "feat: sidebar filters nav items by user permissions, share auth.permissions via Inertia"
```

---

## Self-review

**Spec coverage check:**

| Requirement | Task |
|---|---|
| PrimeVue FloatLabel inputs | Task 4 |
| Layout margin fix | Task 1 |
| Ripple directive | Task 1 |
| CLAUDE.md UI rules | Task 1 |
| PrimeVue Select filters | Task 2 |
| Chip roles in UserCard | Task 3 |
| Admin-first ordering | Task 3 |
| Remove branch_id select from forms | Task 4 |
| Labels in Spanish (roleLabel) | Task 3 (UserCard), Task 2 (Index) |
| PermissionGrid 5-column table | Task 5 |
| Multi-branch UserCard | Task 3 |
| UserShow branch click + grouped data | Task 6 |
| Per-branch permissions backend | Task 7 |
| Clinic-role-module config superadmin | Task 8 |
| Superadmin clinic user management | Task 9 |
| Sidebar permission filtering | Task 10 |
| UpdateUserRequest branch_id optional | Task 4 |
| Module description in PermissionGrid | Task 5 |

All requirements covered.
