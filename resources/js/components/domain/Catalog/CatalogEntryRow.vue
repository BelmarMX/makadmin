<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Pencil, Trash2, Check, X } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import type { CatalogEntry } from '@/pages/Admin/Catalog/Index.vue';
import * as catalogRoutes from '@/actions/App/Http/Controllers/Admin/CatalogController';

const props = defineProps<{
    entry: CatalogEntry;
    type: string;
}>();

const editing = ref(false);
const editName = ref(props.entry.name);
const editIcon = ref(props.entry.icon ?? '');
const editSortOrder = ref(props.entry.sort_order ?? 100);

function save() {
    router.put(catalogRoutes.update(props.entry.id).url, {
        type: props.type,
        name: editName.value,
        icon: editIcon.value || undefined,
        sort_order: editSortOrder.value,
    }, {
        preserveScroll: true,
        onSuccess: () => { editing.value = false; },
    });
}

function archive() {
    if (!confirm(`¿Archivar "${props.entry.name}"?`)) return;
    router.delete(catalogRoutes.destroy(props.entry.id).url, {
        data: { type: props.type },
        preserveScroll: true,
    });
}
</script>

<template>
    <tr class="border-b border-border hover:bg-muted/30 transition-colors">
        <td class="px-4 py-3">
            <div v-if="!editing" class="flex items-center gap-2">
                <span v-if="entry.icon" class="text-muted-foreground text-xs font-mono">{{ entry.icon }}</span>
                <span>{{ entry.name }}</span>
            </div>
            <div v-else class="flex items-center gap-2">
                <Input v-model="editIcon" placeholder="icono" class="h-8 w-28 text-xs" />
                <Input v-model="editName" class="h-8" />
            </div>
        </td>
        <td class="px-4 py-3 text-sm text-muted-foreground">
            <span v-if="!editing">{{ entry.sort_order ?? '—' }}</span>
            <Input v-else v-model.number="editSortOrder" type="number" class="h-8 w-20" />
        </td>
        <td class="px-4 py-3">
            <Badge v-if="entry.is_system" variant="secondary" class="text-xs">Sistema</Badge>
        </td>
        <td class="px-4 py-3 text-right">
            <div v-if="!editing" class="flex justify-end gap-1">
                <Button size="icon" variant="ghost" class="h-7 w-7" @click="editing = true">
                    <Pencil class="h-3.5 w-3.5" />
                </Button>
                <Button size="icon" variant="ghost" class="h-7 w-7 text-destructive hover:text-destructive" @click="archive">
                    <Trash2 class="h-3.5 w-3.5" />
                </Button>
            </div>
            <div v-else class="flex justify-end gap-1">
                <Button size="icon" variant="ghost" class="h-7 w-7 text-success" @click="save">
                    <Check class="h-3.5 w-3.5" />
                </Button>
                <Button size="icon" variant="ghost" class="h-7 w-7" @click="editing = false">
                    <X class="h-3.5 w-3.5" />
                </Button>
            </div>
        </td>
    </tr>
</template>
