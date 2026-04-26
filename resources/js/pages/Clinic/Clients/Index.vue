<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Search, X, Users } from 'lucide-vue-next';
import FloatLabel from 'primevue/floatlabel';
import Select from 'primevue/select';
import { ref, watch } from 'vue';
import ClientCard from '@/components/domain/Patient/ClientCard.vue';
import PatientQuickSearch from '@/components/domain/Patient/PatientQuickSearch.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import * as clientRoutes from '@/actions/App/Http/Controllers/Clinic/ClientController';
import * as patientRoutes from '@/actions/App/Http/Controllers/Clinic/PatientController';
import { clinicSlug } from '@/composables/useClinicSlug';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    clients: {
        data: Array<{
            id: number;
            name: string;
            email?: string | null;
            phone?: string | null;
            is_active: boolean;
            patients_count?: number;
        }>;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    filters: { search?: string | null; status?: string | null };
}>();

const clinic = clinicSlug();
const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '__all__');
const statusOptions = [
    { value: '__all__', label: 'Todos los estados' },
    { value: 'active', label: 'Activos' },
    { value: 'inactive', label: 'Inactivos' },
];
let debounceTimer: ReturnType<typeof setTimeout>;

watch([search, status], () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        router.get(
            clientRoutes.index(clinic).url,
            {
                search: search.value || undefined,
                status: status.value !== '__all__' ? status.value : undefined,
            },
            { preserveState: true, replace: true },
        );
    }, 300);
});

function clearFilters(): void {
    search.value = '';
    status.value = '__all__';
}
</script>

<template>
    <Head title="Tutores y pacientes" />

    <div class="space-y-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Tutores y pacientes</h1>
                <p class="text-sm text-muted-foreground">
                    Registra tutores, localiza mascotas y entra a su ficha clínica.
                </p>
            </div>
            <Button as-child v-ripple>
                <Link :href="clientRoutes.create(clinic).url">
                    <Plus class="h-4 w-4" />
                    Agregar tutor
                </Link>
            </Button>
        </div>

        <div class="grid gap-3 xl:grid-cols-[1fr_220px_340px]">
            <div class="relative">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    v-model="search"
                    placeholder="Buscar por nombre, email o teléfono…"
                    class="pl-9 pr-9"
                />
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
                    v-model="status"
                    :options="statusOptions"
                    option-label="label"
                    option-value="value"
                    input-id="filter-status"
                    class="w-full"
                />
                <label for="filter-status">Estado</label>
            </FloatLabel>

            <PatientQuickSearch
                placeholder="Buscar paciente por nombre o microchip…"
                @select="router.visit(patientRoutes.show({ clinic, patient: $event.id }).url)"
            />
        </div>

        <div
            v-if="clients.data.length === 0"
            class="flex flex-col items-center justify-center rounded-lg border border-dashed border-border py-16 text-center"
        >
            <Users class="mb-4 h-12 w-12 text-muted-foreground" />
            <p class="font-medium text-foreground">
                {{ search ? `Sin resultados para "${search}"` : 'Aún no hay tutores registrados' }}
            </p>
            <p class="text-sm text-muted-foreground">
                {{ search ? 'Prueba con otro término o limpia los filtros.' : 'Agrega el primer tutor para comenzar a registrar mascotas.' }}
            </p>
            <div class="mt-4 flex gap-2">
                <Button
                    v-if="search || status !== '__all__'"
                    variant="ghost"
                    @click="clearFilters"
                >
                    <X class="h-4 w-4" />
                    Limpiar búsqueda
                </Button>
                <Button as-child v-ripple>
                    <Link :href="clientRoutes.create(clinic).url">
                        <Plus class="h-4 w-4" />
                        Agregar tutor
                    </Link>
                </Button>
            </div>
        </div>

        <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2 2xl:grid-cols-4">
            <ClientCard
                v-for="client in clients.data"
                :key="client.id"
                :client="client"
            />
        </div>

        <div v-if="clients.links.length > 3" class="flex justify-center gap-1">
            <template v-for="link in clients.links" :key="link.label">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    :class="[
                        'rounded border px-3 py-1 text-sm transition-colors',
                        link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted',
                    ]"
                    v-html="link.label"
                />
                <span
                    v-else
                    class="rounded border border-transparent px-3 py-1 text-sm text-muted-foreground"
                    v-html="link.label"
                />
            </template>
        </div>
    </div>
</template>
