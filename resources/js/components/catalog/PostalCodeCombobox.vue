<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { Search, X } from 'lucide-vue-next';
import * as catalogRoutes from '@/routes/api/catalog';

interface PostalCodeOption {
    id: number;
    code: string;
    settlement: string;
    settlement_type: string | null;
    state: { id: number; name: string };
    municipality: { id: number; name: string };
}

const props = defineProps<{
    modelValue?: string | null;
    disabled?: boolean;
}>();

const emit = defineEmits<{
    'update:modelValue': [code: string | null];
    'update:postalCode': [code: string | null];
    'update:state': [stateId: number | null];
    'update:municipality': [municipalityId: number | null];
}>();

const search = ref(props.modelValue ?? '');
const options = ref<PostalCodeOption[]>([]);
const open = ref(false);
const loading = ref(false);
const container = ref<HTMLElement | null>(null);
let debounceTimer: ReturnType<typeof setTimeout>;

async function fetchOptions(q: string) {
    if (!q || q.length < 2) { options.value = []; return; }
    loading.value = true;
    try {
        const params = new URLSearchParams({ q, limit: '30' });
        const res = await fetch(`${catalogRoutes.postalCodes().url}?${params}`);
        if (!res.ok) return;
        const json = await res.json();
        options.value = json.data ?? json;
    } finally {
        loading.value = false;
    }
}

function onInput() {
    clearTimeout(debounceTimer);
    open.value = true;
    debounceTimer = setTimeout(() => fetchOptions(search.value), 250);
}

function select(opt: PostalCodeOption) {
    search.value = opt.code;
    open.value = false;
    emit('update:modelValue', opt.code);
    emit('update:postalCode', opt.code);
    emit('update:state', opt.state?.id ?? null);
    emit('update:municipality', opt.municipality?.id ?? null);
}

function clear() {
    search.value = '';
    options.value = [];
    open.value = false;
    emit('update:modelValue', null);
    emit('update:postalCode', null);
    emit('update:state', null);
    emit('update:municipality', null);
}

function onClickOutside(e: MouseEvent) {
    if (container.value && !container.value.contains(e.target as Node)) {
        open.value = false;
    }
}

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
            <input
                v-model="search"
                placeholder="CP o colonia..."
                class="flex-1 bg-transparent outline-none placeholder:text-muted-foreground"
                @input="onInput"
                @focus="open = true"
            />
            <button v-if="search" type="button" class="ml-1 text-muted-foreground hover:text-foreground" @click.stop="clear">
                <X class="h-3.5 w-3.5" />
            </button>
        </div>

        <div v-if="open && (options.length > 0 || loading)" class="absolute z-50 mt-1 w-full rounded-md border border-border bg-card shadow-lg">
            <div v-if="loading" class="px-4 py-3 text-sm text-muted-foreground">Buscando...</div>
            <ul v-else class="max-h-64 overflow-y-auto py-1">
                <li
                    v-for="opt in options"
                    :key="opt.id"
                    class="cursor-pointer px-4 py-2 text-sm hover:bg-muted/60"
                    @mousedown.prevent="select(opt)"
                >
                    <span class="font-mono font-medium">{{ opt.code }}</span>
                    <span class="mx-1 text-muted-foreground">·</span>
                    <span>{{ opt.settlement }}</span>
                    <span v-if="opt.settlement_type" class="ml-1 text-xs text-muted-foreground">({{ opt.settlement_type }})</span>
                    <div class="text-xs text-muted-foreground">{{ opt.municipality?.name }}, {{ opt.state?.name }}</div>
                </li>
            </ul>
        </div>
    </div>
</template>
