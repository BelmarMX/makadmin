<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import CatalogEntryRow from '@/components/domain/Catalog/CatalogEntryRow.vue';
import type { CatalogEntry } from './Index.vue';
import * as catalogRoutes from '@/actions/App/Http/Controllers/Admin/CatalogController';

defineProps<{ entries: CatalogEntry[] }>();

const newName = ref('');
const newMin = ref<number | null>(null);
const newMax = ref<number | null>(null);
const newSortOrder = ref(100);

function create() {
    if (!newName.value.trim()) return;
    router.post(catalogRoutes.store().url, {
        type: 'pet_size',
        name: newName.value,
        sort_order: newSortOrder.value,
    }, {
        preserveScroll: true,
        onSuccess: () => { newName.value = ''; newMin.value = null; newMax.value = null; newSortOrder.value = 100; },
    });
}
</script>

<template>
    <div class="space-y-4 pt-4">
        <div class="flex items-end gap-3">
            <div class="flex-1">
                <Label>Nombre</Label>
                <Input v-model="newName" placeholder="Ej: Miniatura" />
            </div>
            <div class="w-24">
                <Label>Orden</Label>
                <Input v-model.number="newSortOrder" type="number" />
            </div>
            <Button @click="create">
                <Plus class="h-4 w-4" />
                Agregar
            </Button>
        </div>

        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-border text-left text-muted-foreground">
                    <th class="px-4 pb-2 font-medium">Nombre</th>
                    <th class="px-4 pb-2 font-medium">Peso mín (kg)</th>
                    <th class="px-4 pb-2 font-medium">Peso máx (kg)</th>
                    <th class="px-4 pb-2 font-medium">Orden</th>
                    <th class="px-4 pb-2 font-medium">Tipo</th>
                    <th class="px-4 pb-2" />
                </tr>
            </thead>
            <tbody>
                <tr v-for="entry in entries" :key="entry.id" class="border-b border-border hover:bg-muted/30">
                    <td class="px-4 py-3 font-medium">{{ entry.name }}</td>
                    <td class="px-4 py-3 text-muted-foreground">{{ entry.weight_min_kg ?? '—' }}</td>
                    <td class="px-4 py-3 text-muted-foreground">{{ entry.weight_max_kg ?? '—' }}</td>
                    <td class="px-4 py-3 text-muted-foreground">{{ entry.sort_order }}</td>
                    <td class="px-4 py-3">
                        <span v-if="entry.is_system" class="rounded bg-muted px-1.5 py-0.5 text-xs text-muted-foreground">Sistema</span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <CatalogEntryRow :entry="entry" type="pet_size" class="hidden" />
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
