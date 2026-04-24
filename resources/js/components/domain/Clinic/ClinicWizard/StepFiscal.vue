<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

const props = defineProps<{
    form: {
        rfc: string;
        fiscal_regime: string;
        tax_address: string;
        responsible_vet_name: string;
        responsible_vet_license: string;
        errors: Record<string, string>;
    };
    fiscalRegimes: Array<{ value: string; label: string }>;
}>();
</script>

<template>
    <div class="space-y-5">
        <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
                <Label for="rfc">RFC</Label>
                <Input id="rfc" v-model="props.form.rfc" placeholder="XAXX010101000" class="uppercase" />
                <p v-if="props.form.errors.rfc" class="text-xs text-destructive">{{ props.form.errors.rfc }}</p>
            </div>
            <div class="space-y-2">
                <Label for="fiscal_regime">Régimen fiscal</Label>
                <Select v-model="props.form.fiscal_regime">
                    <SelectTrigger id="fiscal_regime">
                        <SelectValue placeholder="Selecciona régimen" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="r in props.fiscalRegimes" :key="r.value" :value="r.value">
                            {{ r.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>

        <div class="space-y-2">
            <Label for="tax_address">Domicilio fiscal</Label>
            <Textarea id="tax_address" v-model="props.form.tax_address" placeholder="Calle, número, colonia, C.P., ciudad, estado" :rows="2" />
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
                <Label for="responsible_vet_name">Médico responsable <span class="text-destructive">*</span></Label>
                <Input id="responsible_vet_name" v-model="props.form.responsible_vet_name" placeholder="Dr. Juan Pérez López" />
                <p v-if="props.form.errors.responsible_vet_name" class="text-xs text-destructive">{{ props.form.errors.responsible_vet_name }}</p>
            </div>
            <div class="space-y-2">
                <Label for="responsible_vet_license">Cédula profesional <span class="text-destructive">*</span></Label>
                <Input id="responsible_vet_license" v-model="props.form.responsible_vet_license" placeholder="12345678" />
                <p v-if="props.form.errors.responsible_vet_license" class="text-xs text-destructive">{{ props.form.errors.responsible_vet_license }}</p>
            </div>
        </div>
    </div>
</template>
