<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { CheckSquare, Eye, Pencil, Plus, Save, Square, Trash2 } from 'lucide-vue-next';
import Checkbox from 'primevue/checkbox';
import { computed, watch } from 'vue';
import { toast } from '@/lib/toast';
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

function buildPermissions(perms: Array<{ name: string }>): Record<string, string[]> {
    const set = new Set((perms ?? []).map((p) => p.name));
    const result: Record<string, string[]> = {};

    for (const module of props.modules) {
        result[module.module_key] = actions
            .filter((action) => set.has(`${module.module_key}.${action.key}`))
            .map((action) => action.key);
    }

    return result;
}

const form = useForm({
    permissions: buildPermissions(props.permissions ?? []),
    branch_id: props.branchId ?? null,
});

watch(
    () => [props.branchId, props.permissions],
    () => {
        form.permissions = buildPermissions(props.permissions ?? []);
        form.branch_id = props.branchId ?? null;
    },
);

function hasPermission(module: string, action: string): boolean {
    return form.permissions[module]?.includes(action) ?? false;
}

function togglePermission(module: string, action: string, checked: boolean): void {
    const current = form.permissions[module] ?? [];
    form.permissions[module] = checked ? [...current, action] : current.filter((value) => value !== action);
}

function allEnabled(module: string): boolean {
    return actions.every((action) => hasPermission(module, action.key));
}

function noneEnabled(module: string): boolean {
    return actions.every((action) => !hasPermission(module, action.key));
}

function toggleModule(module: string): void {
    const allChecked = allEnabled(module);
    form.permissions[module] = allChecked ? [] : actions.map((action) => action.key);
}

function allActionsEnabled(action: string): boolean {
    return props.modules.every((module) => hasPermission(module.module_key, action));
}

function toggleAction(action: string): void {
    const allChecked = allActionsEnabled(action);
    const newState = !allChecked;

    for (const module of props.modules) {
        const current = form.permissions[module.module_key] ?? [];
        form.permissions[module.module_key] = newState
            ? [...new Set([...current, action])]
            : current.filter((value) => value !== action);
    }
}

const allEnabledGlobal = computed(() => actions.every((action) => allActionsEnabled(action.key)));

function toggleAll(): void {
    const newState = !allEnabledGlobal.value;

    for (const module of props.modules) {
        form.permissions[module.module_key] = newState ? actions.map((action) => action.key) : [];
    }
}

function submit() {
    form.patch(props.action, {
        preserveScroll: true,
        onSuccess: () => toast.success('Permisos guardados'),
        onError: () => toast.error('Error al guardar permisos'),
    });
}
</script>

<template>
    <form class="space-y-4" @submit.prevent="submit">
        <div class="overflow-x-auto rounded-lg border border-border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-border bg-muted/50">
                        <th class="flex items-center justify-center px-4 py-3">
                            <button
                                type="button"
                                :title="allEnabledGlobal ? 'Quitar todos' : 'Seleccionar todos'"
                                class="cursor-pointer text-muted-foreground hover:text-foreground"
                                @click="toggleAll"
                            >
                                <component :is="allEnabledGlobal ? CheckSquare : Square" class="h-4 w-4" />
                            </button>
                        </th>
                        <th
                            v-for="action in actions"
                            :key="action.key"
                            class="cursor-pointer px-2 py-3 text-center font-semibold text-foreground"
                            @click="toggleAction(action.key)"
                        >
                            <div class="flex flex-col items-center gap-1">
                                <component :is="action.icon" class="h-4 w-4" />
                                <span class="text-xs">{{ action.label }}</span>
                                <span
                                    class="block h-1 w-1 rounded-full"
                                    :class="allActionsEnabled(action.key) ? 'bg-primary' : 'bg-transparent'"
                                />
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    <tr
                        v-for="module in modules"
                        :key="module.module_key"
                        class="transition-colors hover:bg-muted/30"
                    >
                        <td
                            class="cursor-pointer px-4 py-3"
                            @click="toggleModule(module.module_key)"
                        >
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    class="cursor-pointer text-muted-foreground hover:text-foreground"
                                >
                                    <component :is="allEnabled(module.module_key) ? CheckSquare : noneEnabled(module.module_key) ? Square : CheckSquare" class="h-3.5 w-3.5 opacity-50" />
                                </button>
                                <div>
                                    <p class="font-medium text-foreground">{{ module.label }}</p>
                                    <p v-if="module.description" class="mt-0.5 text-xs text-muted-foreground">{{ module.description }}</p>
                                </div>
                            </div>
                        </td>
                        <td
                            v-for="action in actions"
                            :key="action.key"
                            class="px-2 py-3 text-center"
                        >
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
