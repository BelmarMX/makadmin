<script setup lang="ts">
import { Search, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import clientSearchRoute from '@/actions/App/Http/Controllers/Clinic/Api/ClientSearchController';
import { clinicSlug } from '@/composables/useClinicSlug';

type ClientSearchResult = {
    id: number;
    name: string;
    email?: string | null;
    phone?: string | null;
    patients_count?: number;
};

const props = defineProps<{
    modelValue?: number | null;
    placeholder?: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: number | null];
    select: [value: ClientSearchResult | null];
}>();

const clinic = clinicSlug();
const query = ref('');
const open = ref(false);
const loading = ref(false);
const results = ref<ClientSearchResult[]>([]);
const selectedLabel = ref('');
const skipQueryWatch = ref(false);
let debounceTimer: ReturnType<typeof setTimeout>;

async function searchClients(term: string): Promise<void> {
    if (term.trim().length < 2) {
        results.value = [];
        return;
    }

    loading.value = true;

    try {
        const response = await fetch(`${clientSearchRoute(clinic).url}?q=${encodeURIComponent(term)}`);

        if (!response.ok) {
            results.value = [];
            return;
        }

        results.value = await response.json();
    } finally {
        loading.value = false;
    }
}

function selectClient(client: ClientSearchResult): void {
    skipQueryWatch.value = true;
    selectedLabel.value = client.name;
    query.value = '';
    results.value = [];
    open.value = false;
    emit('update:modelValue', client.id);
    emit('select', client);
}

function clearSelection(): void {
    selectedLabel.value = '';
    query.value = '';
    open.value = false;
    results.value = [];
    emit('update:modelValue', null);
    emit('select', null);
}

watch(query, (value) => {
    if (skipQueryWatch.value) {
        skipQueryWatch.value = false;
        return;
    }

    clearTimeout(debounceTimer);

    if (value.trim().length < 2) {
        results.value = [];
        open.value = false;
        return;
    }

    open.value = true;
    debounceTimer = setTimeout(() => searchClients(value), 300);
});

watch(
    () => props.modelValue,
    (value) => {
        if (!value) {
            selectedLabel.value = '';
        }
    },
);
</script>

<template>
    <div class="relative">
        <div
            class="flex h-10 items-center rounded-md border border-input bg-input px-3 py-2 text-sm"
        >
            <Search class="mr-2 h-4 w-4 shrink-0 text-muted-foreground" />
            <span
                v-if="selectedLabel && !open"
                class="flex-1 truncate text-foreground"
            >
                {{ selectedLabel }}
            </span>
            <input
                v-else
                v-model="query"
                :placeholder="props.placeholder ?? 'Buscar tutor...'"
                class="flex-1 bg-transparent outline-none placeholder:text-muted-foreground"
                @focus="open = true"
            />
            <button
                v-if="props.modelValue || query"
                type="button"
                class="ml-1 text-muted-foreground hover:text-foreground"
                @click="clearSelection"
            >
                <X class="h-3.5 w-3.5" />
            </button>
        </div>

        <div
            v-if="open"
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
                    v-for="client in results"
                    :key="client.id"
                    class="cursor-pointer px-4 py-2 text-sm hover:bg-muted/60"
                    @mousedown.prevent="selectClient(client)"
                >
                    <p class="font-medium text-foreground">{{ client.name }}</p>
                    <p class="text-xs text-muted-foreground">
                        {{ client.email || client.phone || 'Sin contacto' }}
                    </p>
                </li>
            </ul>
            <div v-else class="px-4 py-3 text-sm text-muted-foreground">
                Sin resultados.
            </div>
        </div>
    </div>
</template>
