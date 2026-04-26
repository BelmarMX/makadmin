<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Search, X, Users } from 'lucide-vue-next';
import FloatLabel from 'primevue/floatlabel';
import Select from 'primevue/select';
import { ref, watch } from 'vue';
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
