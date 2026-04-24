<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { AlertCircle } from 'lucide-vue-next';
import { Card, CardContent } from '@/components/ui/card';
import { Switch } from '@/components/ui/switch';
import { Badge } from '@/components/ui/badge';
import * as moduleRoutes from '@/actions/App/Http/Controllers/Admin/ClinicModuleController';

const props = defineProps<{
    clinicId: number;
    module: {
        key: string;
        label: string;
        description: string;
        icon: string;
        dependsOn: string[];
        active: boolean;
    };
}>();

const emit = defineEmits<{ toggled: [key: string, active: boolean] }>();
const loading = ref(false);
const errorMsg = ref('');

function toggle(value: boolean) {
    loading.value = true;
    errorMsg.value = '';
    router.post(
        moduleRoutes.toggle({ clinic: props.clinicId, module: props.module.key }).url,
        { activate: value },
        {
            preserveScroll: true,
            onFinish: () => { loading.value = false; },
            onSuccess: () => emit('toggled', props.module.key, value),
            onError: (errors) => {
                const msg = Object.values(errors)[0] ?? 'No se pudo cambiar el estado del módulo.';
                errorMsg.value = typeof msg === 'string' ? msg : 'Error al cambiar módulo.';
            },
        },
    );
}
</script>

<template>
    <Card :class="['transition-colors', props.module.active ? 'border-primary/30' : '']">
        <CardContent class="p-4">
            <div class="flex items-start gap-4">
                <div class="mt-0.5 flex-1">
                    <div class="flex items-center gap-2">
                        <p class="font-medium text-foreground">{{ props.module.label }}</p>
                        <Badge v-if="props.module.dependsOn.length" variant="outline" class="text-xs">
                            requiere {{ props.module.dependsOn.join(', ') }}
                        </Badge>
                    </div>
                    <p class="mt-1 text-sm text-muted-foreground">{{ props.module.description }}</p>
                    <p v-if="errorMsg" class="mt-2 flex items-center gap-1 text-xs text-destructive">
                        <AlertCircle class="h-3.5 w-3.5 flex-shrink-0" />
                        {{ errorMsg }}
                    </p>
                </div>
                <Switch
                    :model-value="props.module.active"
                    :disabled="loading"
                    @update:model-value="toggle"
                />
            </div>
        </CardContent>
    </Card>
</template>
