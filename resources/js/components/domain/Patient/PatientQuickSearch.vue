<script setup lang="ts">
import { Search, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { Input } from '@/components/ui/input';
import patientSearchRoute from '@/actions/App/Http/Controllers/Clinic/Api/PatientSearchController';
import { clinicSlug } from '@/composables/useClinicSlug';

type PatientSearchResult = {
    id: number;
    name: string;
    microchip?: string | null;
    client?: { id: number; name: string } | null;
    species?: { id: number; name: string } | null;
};

const props = defineProps<{
    placeholder?: string;
}>();

const emit = defineEmits<{
    select: [value: PatientSearchResult];
}>();

const clinic = clinicSlug();
const query = ref('');
const open = ref(false);
const loading = ref(false);
const results = ref<PatientSearchResult[]>([]);
let debounceTimer: ReturnType<typeof setTimeout>;

async function searchPatients(term: string): Promise<void> {
    if (term.trim().length < 2) {
        results.value = [];
        return;
    }

    loading.value = true;

    try {
        const response = await fetch(`${patientSearchRoute(clinic).url}?q=${encodeURIComponent(term)}`);

        if (!response.ok) {
            results.value = [];
            return;
        }

        results.value = await response.json();
    } finally {
        loading.value = false;
    }
}

watch(query, (value) => {
    clearTimeout(debounceTimer);
    open.value = true;
    debounceTimer = setTimeout(() => searchPatients(value), 300);
});
</script>

<template>
    <div class="relative">
        <Search
            class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
        />
        <Input
            v-model="query"
            :placeholder="props.placeholder ?? 'Buscar paciente por nombre o microchip…'"
            class="pl-9 pr-9"
            @focus="open = true"
        />
        <button
            v-if="query"
            type="button"
            class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
            @click="
                query = '';
                open = false;
                results = [];
            "
        >
            <X class="h-4 w-4" />
        </button>

        <div
            v-if="open && query.length >= 2"
            class="absolute z-50 mt-1 w-full rounded-md border border-border bg-card shadow-lg"
        >
            <div v-if="loading" class="px-4 py-3 text-sm text-muted-foreground">
                Buscando…
            </div>
            <ul
                v-else-if="results.length > 0"
                class="max-h-60 overflow-y-auto py-1"
            >
                <li
                    v-for="patient in results"
                    :key="patient.id"
                    class="cursor-pointer px-4 py-2 text-sm hover:bg-muted/60"
                    @mousedown.prevent="emit('select', patient)"
                >
                    <p class="font-medium text-foreground">{{ patient.name }}</p>
                    <p class="text-xs text-muted-foreground">
                        {{ patient.microchip || patient.client?.name || patient.species?.name || 'Sin datos adicionales' }}
                    </p>
                </li>
            </ul>
            <div v-else class="px-4 py-3 text-sm text-muted-foreground">
                Sin resultados.
            </div>
        </div>
    </div>
</template>
