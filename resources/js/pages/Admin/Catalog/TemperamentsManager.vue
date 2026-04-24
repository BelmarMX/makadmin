<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import CatalogEntryRow from '@/components/domain/Catalog/CatalogEntryRow.vue';
import type { CatalogEntry } from './Index.vue';
import * as catalogRoutes from '@/actions/App/Http/Controllers/Admin/CatalogController';

defineProps<{ entries: CatalogEntry[] }>();

const newName = ref('');
const newIcon = ref('');

function create() {
    if (!newName.value.trim()) return;
    router.post(catalogRoutes.store().url, {
        type: 'temperament',
        name: newName.value,
        icon: newIcon.value || undefined,
    }, {
        preserveScroll: true,
        onSuccess: () => { newName.value = ''; newIcon.value = ''; },
    });
}
</script>

<template>
    <div class="space-y-4 pt-4">
        <div class="flex items-end gap-3">
            <div class="flex-1">
                <Label>Nombre</Label>
                <Input v-model="newName" placeholder="Ej: Ansioso" />
            </div>
            <div class="w-36">
                <Label>Ícono</Label>
                <Input v-model="newIcon" placeholder="smile" />
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
                <CatalogEntryRow v-for="entry in entries" :key="entry.id" :entry="entry" type="temperament" />
            </tbody>
        </table>

        <p v-if="entries.length === 0" class="py-8 text-center text-sm text-muted-foreground">
            Sin temperamentos registrados.
        </p>
    </div>
</template>
