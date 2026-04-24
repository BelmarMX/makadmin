<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { MapPin, Phone, Pencil, Trash2, Check, X } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
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

const editing = ref(false);
const deleting = ref(false);

const editForm = useForm({
    name: props.branch.name,
    address: props.branch.address,
    phone: props.branch.phone ?? '',
});

function startEdit() {
    editForm.name = props.branch.name;
    editForm.address = props.branch.address;
    editForm.phone = props.branch.phone ?? '';
    editing.value = true;
}

function cancelEdit() {
    editForm.reset();
    editing.value = false;
}

function saveEdit() {
    editForm.put(
        branchRoutes.update({ clinic: props.clinicId, branch: props.branch.id }).url,
        {
            preserveScroll: true,
            onSuccess: () => { editing.value = false; },
        },
    );
}

function destroy() {
    if (!confirm(`¿Archivar sucursal «${props.branch.name}»?`)) return;
    deleting.value = true;
    import('@inertiajs/vue3').then(({ router }) => {
        router.delete(
            branchRoutes.destroy({ clinic: props.clinicId, branch: props.branch.id }).url,
            { preserveScroll: true, onFinish: () => { deleting.value = false; } },
        );
    });
}
</script>

<template>
    <!-- Modo edición -->
    <div v-if="editing" class="space-y-3 rounded-lg border border-primary/30 bg-primary/5 p-4">
        <div class="space-y-2">
            <Label>Nombre <span class="text-destructive">*</span></Label>
            <Input v-model="editForm.name" placeholder="Sucursal Norte" />
            <p v-if="editForm.errors.name" class="text-xs text-destructive">{{ editForm.errors.name }}</p>
        </div>
        <div class="space-y-2">
            <Label>Dirección <span class="text-destructive">*</span></Label>
            <Textarea v-model="editForm.address" placeholder="Av. Insurgentes 100, CDMX" :rows="2" />
            <p v-if="editForm.errors.address" class="text-xs text-destructive">{{ editForm.errors.address }}</p>
        </div>
        <div class="space-y-2">
            <Label>Teléfono</Label>
            <Input v-model="editForm.phone" type="tel" placeholder="+52 55 1234 5678" />
        </div>
        <div class="flex gap-2">
            <Button size="sm" :disabled="editForm.processing" @click="saveEdit">
                <Check class="h-3.5 w-3.5" />
                Guardar
            </Button>
            <Button size="sm" variant="ghost" @click="cancelEdit">
                <X class="h-3.5 w-3.5" />
                Cancelar
            </Button>
        </div>
    </div>

    <!-- Modo vista -->
    <div v-else class="flex items-start justify-between gap-4 rounded-lg border border-border p-4">
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
        <div class="flex gap-1">
            <Button size="sm" variant="ghost" :title="'Editar sucursal'" @click="startEdit">
                <Pencil class="h-4 w-4" />
            </Button>
            <Button
                v-if="!props.branch.is_main"
                size="sm"
                variant="ghost"
                class="text-destructive hover:text-destructive"
                :disabled="deleting"
                :title="'Archivar sucursal'"
                @click="destroy"
            >
                <Trash2 class="h-4 w-4" />
            </Button>
        </div>
    </div>
</template>
