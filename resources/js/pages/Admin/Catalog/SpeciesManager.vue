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
const newIcon = ref('');
const newSortOrder = ref(100);

function create() {
    if (!newName.value.trim()) return;
    router.post(catalogRoutes.store().url, {
        type: 'species',
        name: newName.value,
        icon: newIcon.value || undefined,
        sort_order: newSortOrder.value,
    }, {
        preserveScroll: true,
        onSuccess: () => { newName.value = ''; newIcon.value = ''; newSortOrder.value = 100; },
    });
}
</script>

<template>
    <div class="space-y-4 pt-4">
        <div class="flex items-end gap-3">
            <div class="flex-1">
                <Label>Nombre</Label>
                <Input v-model="newName" placeholder="Ej: Conejo de Indias" />
            </div>
            <div class="w-32">
                <Label>Ícono</Label>
                <Input v-model="newIcon" placeholder="paw-print" />
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
                    <th class="px-4 pb-2 font-medium">Orden</th>
                    <th class="px-4 pb-2 font-medium">Tipo</th>
                    <th class="px-4 pb-2" />
                </tr>
            </thead>
            <tbody>
                <CatalogEntryRow v-for="entry in entries" :key="entry.id" :entry="entry" type="species" />
            </tbody>
        </table>
    </div>
</template>
