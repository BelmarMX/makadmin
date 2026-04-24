<script setup lang="ts">
import { computed } from 'vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useBranding } from '@/composables/useBranding';

const props = defineProps<{
    form: {
        slug: string;
        commercial_name: string;
        legal_name: string;
        contact_email: string;
        contact_phone: string;
        primary_color: string;
        errors: Record<string, string>;
    };
}>();

const { apexDomain } = useBranding();
const subdomainPreview = computed(() => props.form.slug ? `${props.form.slug}.${apexDomain.value}` : `tu-clinica.${apexDomain.value}`);
</script>

<template>
    <div class="space-y-5">
        <div class="space-y-2">
            <Label for="slug">Subdominio <span class="text-destructive">*</span></Label>
            <div class="flex items-center rounded-md border border-input bg-muted/30">
                <Input
                    id="slug"
                    v-model="props.form.slug"
                    placeholder="mivet"
                    class="border-0 bg-transparent focus-visible:ring-0 lowercase"
                />
                <span class="pr-3 text-sm text-muted-foreground">.{{ apexDomain }}</span>
            </div>
            <p class="text-xs text-muted-foreground">URL: {{ subdomainPreview }}</p>
            <p v-if="props.form.errors.slug" class="text-xs text-destructive">{{ props.form.errors.slug }}</p>
        </div>

        <div class="grid grid-cols-2 gap-4 xl:grid-cols-3">
            <div class="space-y-2">
                <Label for="commercial_name">Nombre comercial <span class="text-destructive">*</span></Label>
                <Input id="commercial_name" v-model="props.form.commercial_name" placeholder="Clínica Veterinaria XYZ" />
                <p v-if="props.form.errors.commercial_name" class="text-xs text-destructive">{{ props.form.errors.commercial_name }}</p>
            </div>
            <div class="space-y-2 xl:col-span-2">
                <Label for="legal_name">Razón social <span class="text-destructive">*</span></Label>
                <Input id="legal_name" v-model="props.form.legal_name" placeholder="XYZ Servicios Veterinarios S.A. de C.V." />
                <p v-if="props.form.errors.legal_name" class="text-xs text-destructive">{{ props.form.errors.legal_name }}</p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 xl:grid-cols-3">
            <div class="space-y-2">
                <Label for="contact_email">Email de contacto <span class="text-destructive">*</span></Label>
                <Input id="contact_email" v-model="props.form.contact_email" type="email" />
                <p v-if="props.form.errors.contact_email" class="text-xs text-destructive">{{ props.form.errors.contact_email }}</p>
            </div>
            <div class="space-y-2">
                <Label for="contact_phone">Teléfono <span class="text-destructive">*</span></Label>
                <Input id="contact_phone" v-model="props.form.contact_phone" type="tel" placeholder="+52 55 1234 5678" />
                <p v-if="props.form.errors.contact_phone" class="text-xs text-destructive">{{ props.form.errors.contact_phone }}</p>
            </div>
            <div class="space-y-2">
                <Label for="primary_color">Color primario</Label>
                <div class="flex items-center gap-3 pt-1">
                    <input id="primary_color" v-model="props.form.primary_color" type="color" class="h-9 w-16 cursor-pointer rounded border border-input bg-transparent p-1" />
                    <span class="font-mono text-sm text-muted-foreground">{{ props.form.primary_color || '#3b82f6' }}</span>
                </div>
            </div>
        </div>

    </div>
</template>
