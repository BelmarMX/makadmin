<script setup lang="ts">
import { ref, watch, onMounted, onBeforeUnmount } from 'vue';
import { Search, X, ChevronDown } from 'lucide-vue-next';
import { Input } from '@/components/ui/input';

export interface CatalogOption {
    id: number;
    name: string;
    [key: string]: unknown;
}

const props = withDefaults(defineProps<{
    modelValue?: number | null;
    endpoint: string;
    placeholder?: string;
    parentId?: number | null;
    disabled?: boolean;
}>(), {
    modelValue: null,
    placeholder: 'Seleccionar...',
    parentId: null,
    disabled: false,
});

const emit = defineEmits<{
    'update:modelValue': [value: number | null];
    'select': [option: CatalogOption];
}>();

const search = ref('');
const options = ref<CatalogOption[]>([]);
const selectedLabel = ref('');
const open = ref(false);
const loading = ref(false);
const container = ref<HTMLElement | null>(null);
let debounceTimer: ReturnType<typeof setTimeout>;

async function fetchOptions(q = '') {
    loading.value = true;
    try {
        const params = new URLSearchParams({ limit: '30' });
        if (q) params.set('q', q);
        if (props.parentId) params.set('parent_id', String(props.parentId));

        const res = await fetch(`${props.endpoint}?${params}`);
        if (!res.ok) return;
        const json = await res.json();
        options.value = json.data ?? json;
    } finally {
        loading.value = false;
    }
}

function onInput(val: string) {
    clearTimeout(debounceTimer);
    open.value = true;
    debounceTimer = setTimeout(() => fetchOptions(val), 250);
}

function select(option: CatalogOption) {
    selectedLabel.value = option.name;
    search.value = '';
    open.value = false;
    emit('update:modelValue', option.id);
    emit('select', option);
}

function clear() {
    selectedLabel.value = '';
    search.value = '';
    options.value = [];
    open.value = false;
    emit('update:modelValue', null);
}

function onFocus() {
    open.value = true;
    if (options.value.length === 0) fetchOptions();
}

function onClickOutside(e: MouseEvent) {
    if (container.value && !container.value.contains(e.target as Node)) {
        open.value = false;
        search.value = '';
    }
}

watch(() => props.parentId, () => {
    options.value = [];
    if (props.modelValue) clear();
    else fetchOptions();
});

watch(() => props.modelValue, async (id) => {
    if (!id) { selectedLabel.value = ''; return; }
    if (!selectedLabel.value) {
        const res = await fetch(`${props.endpoint}?limit=1`);
        if (res.ok) {
            const json = await res.json();
            const found = (json.data ?? json).find((o: CatalogOption) => o.id === id);
            if (found) selectedLabel.value = found.name;
        }
    }
}, { immediate: true });

onMounted(() => document.addEventListener('mousedown', onClickOutside));
onBeforeUnmount(() => document.removeEventListener('mousedown', onClickOutside));
</script>

<template>
    <div ref="container" class="relative">
        <div
            class="flex h-10 items-center rounded-md border border-input bg-input px-3 py-2 text-sm ring-offset-background focus-within:ring-2 focus-within:ring-ring focus-within:ring-offset-2"
            :class="{ 'opacity-50 pointer-events-none': disabled }"
        >
            <Search class="mr-2 h-4 w-4 shrink-0 text-muted-foreground" />
            <span v-if="selectedLabel && !open" class="flex-1 truncate text-foreground">{{ selectedLabel }}</span>
            <input
                v-else
                v-model="search"
                :placeholder="selectedLabel || placeholder"
                class="flex-1 bg-transparent outline-none placeholder:text-muted-foreground"
                @input="onInput(search)"
                @focus="onFocus"
            />
            <button v-if="modelValue" type="button" class="ml-1 text-muted-foreground hover:text-foreground" @click.stop="clear">
                <X class="h-3.5 w-3.5" />
            </button>
            <ChevronDown v-else class="ml-1 h-3.5 w-3.5 text-muted-foreground" />
        </div>

        <div
            v-if="open"
            class="absolute z-50 mt-1 w-full rounded-md border border-border bg-card shadow-lg"
        >
            <div v-if="loading" class="px-4 py-3 text-sm text-muted-foreground">Buscando...</div>
            <ul v-else-if="options.length > 0" class="max-h-60 overflow-y-auto py-1">
                <li
                    v-for="opt in options"
                    :key="opt.id"
                    class="cursor-pointer px-4 py-2 text-sm hover:bg-muted/60"
                    :class="{ 'bg-muted/40 font-medium': opt.id === modelValue }"
                    @mousedown.prevent="select(opt)"
                >
                    <slot name="option" :option="opt">{{ opt.name }}</slot>
                </li>
            </ul>
            <div v-else class="px-4 py-3 text-sm text-muted-foreground">Sin resultados.</div>
        </div>
    </div>
</template>
