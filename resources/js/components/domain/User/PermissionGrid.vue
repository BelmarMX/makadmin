<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    action: string;
    modules: Array<{ module_key: string }>;
    permissions?: Array<{ name: string }>;
}>();

const actions = [
    { key: 'view', label: 'Ver' },
    { key: 'create', label: 'Crear' },
    { key: 'update', label: 'Editar' },
    { key: 'delete', label: 'Eliminar' },
];

const initialPermissions = new Set((props.permissions ?? []).map((permission) => permission.name));
const permissions: Record<string, string[]> = {};

for (const module of props.modules) {
    permissions[module.module_key] = actions
        .filter((action) => initialPermissions.has(`${module.module_key}.${action.key}`))
        .map((action) => action.key);
}

const form = useForm({ permissions });

function submit() {
    form.patch(props.action, { preserveScroll: true });
}
</script>

<template>
    <form class="space-y-4" @submit.prevent="submit">
        <div class="overflow-hidden rounded-lg border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50">
                    <tr>
                        <th class="p-3 text-left font-medium">Módulo</th>
                        <th v-for="action in actions" :key="action.key" class="p-3 text-left font-medium">
                            {{ action.label }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="module in modules" :key="module.module_key" class="border-t">
                        <td class="p-3 font-medium">{{ module.module_key }}</td>
                        <td v-for="action in actions" :key="action.key" class="p-3">
                            <input
                                v-model="form.permissions[module.module_key]"
                                type="checkbox"
                                :value="action.key"
                                class="h-4 w-4 rounded border-input"
                            />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <Button :disabled="form.processing">
            <Save class="h-4 w-4" />
            Guardar permisos
        </Button>
    </form>
</template>
