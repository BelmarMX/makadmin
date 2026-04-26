<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Save } from 'lucide-vue-next';
import Checkbox from 'primevue/checkbox';
import { ref } from 'vue';
import { toast } from '@/lib/toast';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AdminLayout from '@/layouts/AdminLayout.vue';
import * as clinicRoutes from '@/actions/App/Http/Controllers/Admin/ClinicController';
import * as roleModuleRoutes from '@/actions/App/Http/Controllers/Admin/ClinicRoleModuleController';

defineOptions({ layout: AdminLayout });

const props = defineProps<{
    clinic: { id: number; commercial_name: string };
    role: string;
    roleLabel: string;
    modules: Array<{ key: string; label: string; description: string; icon: string; is_enabled: boolean }>;
}>();

const enabledModules = ref<string[]>(
    props.modules.filter((module) => module.is_enabled).map((module) => module.key),
);

function isEnabled(moduleKey: string): boolean {
    return enabledModules.value.includes(moduleKey);
}

function toggleModule(moduleKey: string, enabled: boolean) {
    enabledModules.value = enabled
        ? [...new Set([...enabledModules.value, moduleKey])]
        : enabledModules.value.filter((key) => key !== moduleKey);
}

function save() {
    router.put(
        roleModuleRoutes.update({ clinic: props.clinic.id }).url,
        {
            role: props.role,
            enabled_modules: enabledModules.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => toast.success('Módulos guardados'),
            onError: () => toast.error('Error al guardar módulos'),
        },
    );
}
</script>

<template>
    <Head :title="`Módulos por rol - ${roleLabel}`" />

    <div class="space-y-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Roles y módulos</h1>
                <p class="text-sm text-muted-foreground">{{ props.clinic.commercial_name }} · {{ roleLabel }}</p>
            </div>
            <div class="flex gap-2">
                <Button variant="outline" as-child>
                    <Link :href="clinicRoutes.show({ clinic: props.clinic.id }).url">
                        <ArrowLeft class="h-4 w-4" />
                        Volver
                    </Link>
                </Button>
                <Button v-ripple @click="save">
                    <Save class="h-4 w-4" />
                    Guardar
                </Button>
            </div>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Acceso por módulo</CardTitle>
            </CardHeader>
            <CardContent class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                <label
                    v-for="module in modules"
                    :key="module.key"
                    class="flex cursor-pointer items-start gap-3 rounded-lg border p-4 transition-colors hover:border-primary/50"
                    :class="isEnabled(module.key) ? 'border-primary/30 bg-primary/1' : 'border-border'"
                >
                    <Checkbox
                        :model-value="isEnabled(module.key)"
                        binary
                        @update:model-value="toggleModule(module.key, Boolean($event))"
                    />
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-foreground">{{ module.label }}</p>
                        <p class="text-xs text-muted-foreground">{{ module.description }}</p>
                    </div>
                </label>
            </CardContent>
        </Card>
    </div>
</template>
