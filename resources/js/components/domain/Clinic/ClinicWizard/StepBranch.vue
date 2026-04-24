<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

const props = defineProps<{
    form: {
        main_branch: { name: string; address: string; phone: string };
        errors: Record<string, string>;
    };
}>();
</script>

<template>
    <div class="space-y-5">
        <p class="text-sm text-muted-foreground">Esta sucursal será la principal. Podrás agregar más desde el detalle de la clínica.</p>

        <div class="space-y-2">
            <Label for="branch_name">Nombre de la sucursal <span class="text-destructive">*</span></Label>
            <Input id="branch_name" v-model="props.form.main_branch.name" placeholder="Sucursal Matriz" />
            <p v-if="props.form.errors['main_branch.name']" class="text-xs text-destructive">{{ props.form.errors['main_branch.name'] }}</p>
        </div>

        <div class="space-y-2">
            <Label for="branch_address">Dirección <span class="text-destructive">*</span></Label>
            <Textarea id="branch_address" v-model="props.form.main_branch.address" placeholder="Av. Insurgentes Sur 1234, Col. Del Valle, CDMX" :rows="2" />
            <p v-if="props.form.errors['main_branch.address']" class="text-xs text-destructive">{{ props.form.errors['main_branch.address'] }}</p>
        </div>

        <div class="space-y-2">
            <Label for="branch_phone">Teléfono de sucursal</Label>
            <Input id="branch_phone" v-model="props.form.main_branch.phone" type="tel" placeholder="+52 55 1234 5678" />
        </div>
    </div>
</template>
