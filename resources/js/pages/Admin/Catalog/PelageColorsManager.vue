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
const newHex = ref('');

function create() {
    if (!newName.value.trim()) return;
    router.post(catalogRoutes.store().url, {
        type: 'pelage_color',
        name: newName.value,
        hex: newHex.value || undefined,
    }, {
        preserveScroll: true,
        onSuccess: () => { newName.value = ''; newHex.value = ''; },
    });
}
</script>

<template>
    <div class="space-y-4 pt-4">
        <div class="flex items-end gap-3">
            <div class="flex-1">
                <Label>Nombre del color</Label>
                <Input v-model="newName" placeholder="Ej: Azul acero" />
            </div>
            <div class="w-36">
                <Label>Color (hex)</Label>
                <Input v-model="newHex" placeholder="#3a7fd5" />
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
                    <th class="px-4 pb-2 font-medium">Muestra</th>
                    <th class="px-4 pb-2 font-medium">Tipo</th>
                    <th class="px-4 pb-2" />
                </tr>
            </thead>
            <tbody>
                <tr v-for="entry in entries" :key="entry.id" class="border-b border-border hover:bg-muted/30">
                    <td class="px-4 py-3">{{ entry.name }}</td>
                    <td class="px-4 py-3">
                        <span
                            v-if="entry.hex"
                            :style="{ backgroundColor: entry.hex }"
                            class="inline-block h-5 w-5 rounded-sm border border-border"
                        />
                        <span v-else class="text-muted-foreground text-xs">—</span>
                    </td>
                    <td class="px-4 py-3">
                        <span v-if="entry.is_system" class="rounded bg-muted px-1.5 py-0.5 text-xs text-muted-foreground">Sistema</span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <CatalogEntryRow :entry="entry" type="pelage_color" class="hidden" />
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
