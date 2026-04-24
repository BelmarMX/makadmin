<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import CatalogEntryRow from '@/components/domain/Catalog/CatalogEntryRow.vue';
import type { CatalogEntry } from './Index.vue';
import * as catalogRoutes from '@/actions/App/Http/Controllers/Admin/CatalogController';

defineProps<{
    entries: (CatalogEntry & { species: { id: number; name: string } })[];
    speciesList: CatalogEntry[];
}>();

const newName = ref('');
const newSpeciesId = ref<string>('');

function create() {
    if (!newName.value.trim() || !newSpeciesId.value) return;
    router.post(catalogRoutes.store().url, {
        type: 'breed',
        name: newName.value,
        species_id: Number(newSpeciesId.value),
    }, {
        preserveScroll: true,
        onSuccess: () => { newName.value = ''; newSpeciesId.value = ''; },
    });
}
</script>

<template>
    <div class="space-y-4 pt-4">
        <div class="flex items-end gap-3">
            <div class="w-48">
                <Label>Especie</Label>
                <Select v-model="newSpeciesId">
                    <SelectTrigger>
                        <SelectValue placeholder="Selecciona" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="s in speciesList" :key="s.id" :value="String(s.id)">
                            {{ s.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <div class="flex-1">
                <Label>Nombre de la raza</Label>
                <Input v-model="newName" placeholder="Ej: Labrador Retriever" />
            </div>
            <Button @click="create">
                <Plus class="h-4 w-4" />
                Agregar
            </Button>
        </div>

        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-border text-left text-muted-foreground">
                    <th class="px-4 pb-2 font-medium">Raza</th>
                    <th class="px-4 pb-2 font-medium">Especie</th>
                    <th class="px-4 pb-2 font-medium">Tipo</th>
                    <th class="px-4 pb-2" />
                </tr>
            </thead>
            <tbody>
                <tr v-for="entry in entries" :key="entry.id" class="border-b border-border hover:bg-muted/30">
                    <td class="px-4 py-3">{{ entry.name }}</td>
                    <td class="px-4 py-3 text-muted-foreground">{{ entry.species?.name }}</td>
                    <td class="px-4 py-3">
                        <span v-if="entry.is_system" class="rounded bg-muted px-1.5 py-0.5 text-xs text-muted-foreground">Sistema</span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <CatalogEntryRow :entry="entry" type="breed" class="hidden" />
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
