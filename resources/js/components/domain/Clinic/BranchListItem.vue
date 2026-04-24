<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { MapPin, Phone, Pencil, Trash2 } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import * as branchRoutes from '@/actions/App/Http/Controllers/Admin/ClinicBranchController';

const props = defineProps<{
    clinicId: number;
    branch: {
        id: number;
        name: string;
        address: string;
        phone?: string | null;
        is_main: boolean;
        is_active: boolean;
    };
}>();

const emit = defineEmits<{ edit: [branch: typeof props.branch] }>();
const deleting = ref(false);

function destroy() {
    if (!confirm(`¿Archivar sucursal «${props.branch.name}»?`)) return;
    deleting.value = true;
    router.delete(
        branchRoutes.destroy({ clinic: props.clinicId, branch: props.branch.id }).url,
        { preserveScroll: true, onFinish: () => { deleting.value = false; } },
    );
}
</script>

<template>
    <div class="flex items-start justify-between gap-4 rounded-lg border border-border p-4">
        <div class="flex-1 space-y-1">
            <div class="flex items-center gap-2">
                <p class="font-medium text-foreground">{{ props.branch.name }}</p>
                <Badge v-if="props.branch.is_main" variant="outline" class="text-xs">Principal</Badge>
            </div>
            <p class="flex items-center gap-1 text-sm text-muted-foreground">
                <MapPin class="h-3 w-3" />
                {{ props.branch.address }}
            </p>
            <p v-if="props.branch.phone" class="flex items-center gap-1 text-sm text-muted-foreground">
                <Phone class="h-3 w-3" />
                {{ props.branch.phone }}
            </p>
        </div>
        <div class="flex gap-2">
            <Button size="sm" variant="ghost" @click="emit('edit', props.branch)">
                <Pencil class="h-4 w-4" />
            </Button>
            <Button
                v-if="!props.branch.is_main"
                size="sm"
                variant="ghost"
                class="text-destructive hover:text-destructive"
                :disabled="deleting"
                @click="destroy"
            >
                <Trash2 class="h-4 w-4" />
            </Button>
        </div>
    </div>
</template>
