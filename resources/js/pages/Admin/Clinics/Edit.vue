<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import * as clinicRoutes from '@/actions/App/Http/Controllers/Admin/ClinicController';

defineOptions({ layout: AdminLayout });

const props = defineProps<{
    clinic: {
        id: number;
        slug: string;
        commercial_name: string;
        legal_name: string;
        rfc?: string | null;
        fiscal_regime?: string | null;
        tax_address?: string | null;
        responsible_vet_name: string;
        responsible_vet_license: string;
        contact_phone: string;
        contact_email: string;
        primary_color?: string | null;
    };
    fiscalRegimes: Array<{ value: string; label: string }>;
}>();

const form = useForm({
    slug: props.clinic.slug,
    commercial_name: props.clinic.commercial_name,
    legal_name: props.clinic.legal_name,
    rfc: props.clinic.rfc ?? '',
    fiscal_regime: props.clinic.fiscal_regime ?? '',
    tax_address: props.clinic.tax_address ?? '',
    responsible_vet_name: props.clinic.responsible_vet_name,
    responsible_vet_license: props.clinic.responsible_vet_license,
    contact_phone: props.clinic.contact_phone,
    contact_email: props.clinic.contact_email,
    primary_color: props.clinic.primary_color ?? '#3b82f6',
    logo: null as File | null,
});

function submit() {
    form.put(clinicRoutes.update({ clinic: props.clinic.id }).url);
}
</script>

<template>
    <Head title="Editar clínica" />

    <div class="mx-auto max-w-2xl space-y-6">
        <h1 class="text-2xl font-bold text-foreground">Editar — {{ props.clinic.commercial_name }}</h1>

        <Card>
            <CardHeader><CardTitle>Datos generales</CardTitle></CardHeader>
            <CardContent class="space-y-5">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="slug">Subdominio <span class="text-destructive">*</span></Label>
                        <Input id="slug" v-model="form.slug" class="lowercase" />
                        <p v-if="form.errors.slug" class="text-xs text-destructive">{{ form.errors.slug }}</p>
                    </div>
                    <div class="space-y-2">
                        <Label for="commercial_name">Nombre comercial <span class="text-destructive">*</span></Label>
                        <Input id="commercial_name" v-model="form.commercial_name" />
                        <p v-if="form.errors.commercial_name" class="text-xs text-destructive">{{ form.errors.commercial_name }}</p>
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="legal_name">Razón social <span class="text-destructive">*</span></Label>
                    <Input id="legal_name" v-model="form.legal_name" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="contact_email">Email <span class="text-destructive">*</span></Label>
                        <Input id="contact_email" v-model="form.contact_email" type="email" />
                    </div>
                    <div class="space-y-2">
                        <Label for="contact_phone">Teléfono <span class="text-destructive">*</span></Label>
                        <Input id="contact_phone" v-model="form.contact_phone" type="tel" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="rfc">RFC</Label>
                        <Input id="rfc" v-model="form.rfc" class="uppercase" />
                    </div>
                    <div class="space-y-2">
                        <Label>Régimen fiscal</Label>
                        <Select v-model="form.fiscal_regime">
                            <SelectTrigger>
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
                    <Textarea id="tax_address" v-model="form.tax_address" :rows="2" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="responsible_vet_name">Médico responsable <span class="text-destructive">*</span></Label>
                        <Input id="responsible_vet_name" v-model="form.responsible_vet_name" />
                    </div>
                    <div class="space-y-2">
                        <Label for="responsible_vet_license">Cédula <span class="text-destructive">*</span></Label>
                        <Input id="responsible_vet_license" v-model="form.responsible_vet_license" />
                    </div>
                </div>
            </CardContent>
        </Card>

        <div class="flex justify-end gap-3">
            <Button variant="outline" @click="$inertia.visit(clinicRoutes.show({ clinic: props.clinic.id }))">
                Cancelar
            </Button>
            <Button :disabled="form.processing" @click="submit">
                Guardar cambios
            </Button>
        </div>
    </div>
</template>
