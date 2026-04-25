<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import Checkbox from 'primevue/checkbox';
import { CheckSquare, Eye, Pencil, Plus, Save, Trash2 } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    action: string;
    modules: Array<{ module_key: string; label: string }>;
    permissions?: Array<{ name: string }>;
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

const form = useForm({ permissions });

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
        <div class="grid gap-3">
            <article
                v-for="module in modules"
                :key="module.module_key"
                class="rounded-lg border border-border bg-card p-4"
            >
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary">
                            <CheckSquare class="h-4 w-4" />
                        </span>
                        <div class="min-w-0">
                            <h3 class="truncate text-sm font-semibold text-foreground">{{ module.label }}</h3>
                            <p class="truncate text-xs text-muted-foreground">{{ module.module_key }}</p>
                        </div>
                    </div>

                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                        <label
                            v-for="action in actions"
                            :key="action.key"
                            class="flex min-h-10 cursor-pointer items-center gap-2 rounded-md border border-border bg-background px-3 py-2 text-sm transition-colors hover:border-primary/50"
                            :class="{ 'border-primary bg-primary/5': hasPermission(module.module_key, action.key) }"
                        >
                            <Checkbox
                                :model-value="hasPermission(module.module_key, action.key)"
                                binary
                                @update:model-value="togglePermission(module.module_key, action.key, Boolean($event))"
                            />
                            <component :is="action.icon" class="h-4 w-4 shrink-0 text-muted-foreground" />
                            <span class="font-medium text-foreground">{{ action.label }}</span>
                        </label>
                    </div>
                </div>
            </article>
        </div>

        <Button :disabled="form.processing">
            <Save class="h-4 w-4" />
            Guardar permisos
        </Button>
    </form>
</template>
