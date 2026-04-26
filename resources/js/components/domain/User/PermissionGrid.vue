<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Eye, Pencil, Plus, Save, Trash2 } from 'lucide-vue-next';
import Checkbox from 'primevue/checkbox';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    action: string;
    modules: Array<{ module_key: string; label: string; description?: string }>;
    permissions?: Array<{ name: string }>;
    branchId?: number | null;
}>();

const actions = [
    { key: 'view', label: 'Ver', icon: Eye },
    { key: 'create', label: 'Crear', icon: Plus },
    { key: 'update', label: 'Editar', icon: Pencil },
    { key: 'delete', label: 'Eliminar', icon: Trash2 },
];

const initialPermissions = new Set((props.permissions ?? []).map((permission) => permission.name));
const permissions: Record<string, string[]> = {};

for (const module of props.modules) {
    permissions[module.module_key] = actions
        .filter((action) => initialPermissions.has(`${module.module_key}.${action.key}`))
        .map((action) => action.key);
}

const form = useForm({ permissions, branch_id: props.branchId ?? null });

function hasPermission(module: string, action: string): boolean {
    return form.permissions[module]?.includes(action) ?? false;
}

function togglePermission(module: string, action: string, checked: boolean): void {
    const current = form.permissions[module] ?? [];
    form.permissions[module] = checked ? [...current, action] : current.filter((value) => value !== action);
}

function submit() {
    form.patch(props.action, { preserveScroll: true });
}
</script>

<template>
    <form class="space-y-4" @submit.prevent="submit">
        <div class="overflow-x-auto rounded-lg border border-border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-border bg-muted/50">
                        <th class="px-4 py-3 text-left font-semibold text-foreground">Permiso</th>
                        <th v-for="action in actions" :key="action.key" class="w-24 px-2 py-3 text-center font-semibold text-foreground">
                            <div class="flex flex-col items-center gap-1">
                                <component :is="action.icon" class="h-4 w-4" />
                                <span class="text-xs">{{ action.label }}</span>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr v-for="module in modules" :key="module.module_key" class="transition-colors hover:bg-muted/30">
                        <td class="px-4 py-3">
                            <p class="font-medium text-foreground">{{ module.label }}</p>
                            <p v-if="module.description" class="mt-0.5 text-xs text-muted-foreground">{{ module.description }}</p>
                        </td>
                        <td v-for="action in actions" :key="action.key" class="px-2 py-3 text-center">
                            <Checkbox
                                :model-value="hasPermission(module.module_key, action.key)"
                                binary
                                @update:model-value="togglePermission(module.module_key, action.key, Boolean($event))"
                            />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Button :disabled="form.processing" v-ripple>
            <Save class="h-4 w-4" />
            Guardar permisos
        </Button>
    </form>
</template>
