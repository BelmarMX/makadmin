<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Building2, IdCard, Mail, MapPin, Phone, Save, User } from 'lucide-vue-next';
import FloatLabel from 'primevue/floatlabel';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import { ref } from 'vue';
import { toast } from '@/lib/toast';
import ClientAvatarUpload from '@/components/domain/Patient/ClientAvatarUpload.vue';
import ClientLocationFields from '@/components/domain/Patient/ClientLocationFields.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import * as clientRoutes from '@/actions/App/Http/Controllers/Clinic/ClientController';
import { clinicSlug } from '@/composables/useClinicSlug';

defineOptions({ layout: AppLayout });

const clinic = clinicSlug();
const avatarPreview = ref<string | null>(null);

const form = useForm<{
    name: string;
    email: string;
    phone: string;
    phone_alt: string;
    avatar: File | null;
    address: string;
    colonia: string;
    city: string;
    state: string;
    postal_code: string;
    curp: string;
    rfc: string;
    notes: string;
}>({
    name: '',
    email: '',
    phone: '',
    phone_alt: '',
    avatar: null,
    address: '',
    colonia: '',
    city: '',
    state: '',
    postal_code: '',
    curp: '',
    rfc: '',
    notes: '',
});

function submit(): void {
    form.post(clientRoutes.store(clinic).url, {
        forceFormData: true,
        onSuccess: () => toast.success('Tutor registrado'),
        onError: () => toast.error('Error al registrar tutor'),
    });
}
</script>

<template>
    <Head title="Nuevo tutor" />

    <form class="space-y-6" @submit.prevent="submit">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Nuevo tutor</h1>
                <p class="text-sm text-muted-foreground">Datos de contacto, dirección y referencia fiscal.</p>
            </div>
            <div class="flex gap-2">
                <Button variant="outline" as-child v-tooltip.bottom="'Volver a tutores'">
                    <Link :href="clientRoutes.index(clinic).url">
                        <ArrowLeft class="h-4 w-4" />
                    </Link>
                </Button>
                <Button :disabled="form.processing" v-ripple>
                    <Save class="h-4 w-4" />
                    Guardar
                </Button>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-[12rem_1fr]">
            <ClientAvatarUpload
                v-model="form.avatar"
                :preview="avatarPreview"
                :name="form.name"
                :error="form.errors.avatar"
                @update:preview="avatarPreview = $event"
            />

            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                <div class="grid gap-1">
                <FloatLabel variant="on">
                    <IconField>
                        <InputIcon>
                            <User class="h-4 w-4 text-muted-foreground" />
                        </InputIcon>
                        <InputText id="name" v-model="form.name" class="w-full" />
                    </IconField>
                    <label for="name">Nombre</label>
                </FloatLabel>
                <InputError :message="form.errors.name" />
            </div>
            <div class="grid gap-1">
                <FloatLabel variant="on">
                    <IconField>
                        <InputIcon>
                            <Mail class="h-4 w-4 text-muted-foreground" />
                        </InputIcon>
                        <InputText id="email" v-model="form.email" type="email" class="w-full" />
                    </IconField>
                    <label for="email">Email</label>
                </FloatLabel>
                <InputError :message="form.errors.email" />
            </div>
            <div class="grid gap-1">
                <FloatLabel variant="on">
                    <IconField>
                        <InputIcon>
                            <Phone class="h-4 w-4 text-muted-foreground" />
                        </InputIcon>
                        <InputText id="phone" v-model="form.phone" class="w-full" />
                    </IconField>
                    <label for="phone">Teléfono</label>
                </FloatLabel>
                <InputError :message="form.errors.phone" />
            </div>
            <div class="grid gap-1">
                <FloatLabel variant="on">
                    <IconField>
                        <InputIcon>
                            <Phone class="h-4 w-4 text-muted-foreground" />
                        </InputIcon>
                        <InputText id="phone_alt" v-model="form.phone_alt" class="w-full" />
                    </IconField>
                    <label for="phone_alt">Teléfono alterno</label>
                </FloatLabel>
                <InputError :message="form.errors.phone_alt" />
            </div>
                <div class="grid gap-1 xl:col-span-3">
                <FloatLabel variant="on">
                    <IconField>
                        <InputIcon>
                            <MapPin class="h-4 w-4 text-muted-foreground" />
                        </InputIcon>
                        <InputText id="address" v-model="form.address" class="w-full" />
                    </IconField>
                    <label for="address">Dirección</label>
                </FloatLabel>
                <InputError :message="form.errors.address" />
            </div>

                <div class="md:col-span-2 xl:col-span-3">
                    <ClientLocationFields
                        :postal-code="form.postal_code"
                        :state="form.state"
                        :city="form.city"
                        :colonia="form.colonia"
                        :errors="form.errors"
                        @update:postal-code="form.postal_code = $event"
                        @update:state="form.state = $event"
                        @update:city="form.city = $event"
                        @update:colonia="form.colonia = $event"
                    />
                </div>

            <div class="grid gap-1">
                <FloatLabel variant="on">
                    <IconField>
                        <InputIcon>
                            <IdCard class="h-4 w-4 text-muted-foreground" />
                        </InputIcon>
                        <InputText id="curp" v-model="form.curp" class="w-full" />
                    </IconField>
                    <label for="curp">CURP</label>
                </FloatLabel>
                <InputError :message="form.errors.curp" />
            </div>
            <div class="grid gap-1">
                <FloatLabel variant="on">
                    <IconField>
                        <InputIcon>
                            <Building2 class="h-4 w-4 text-muted-foreground" />
                        </InputIcon>
                        <InputText id="rfc" v-model="form.rfc" class="w-full" />
                    </IconField>
                    <label for="rfc">RFC</label>
                </FloatLabel>
                <InputError :message="form.errors.rfc" />
            </div>
            <div class="grid gap-1 md:col-span-2 xl:col-span-3">
                <FloatLabel variant="on">
                    <Textarea id="notes" v-model="form.notes" rows="4" class="w-full" />
                    <label for="notes">Notas</label>
                </FloatLabel>
                <InputError :message="form.errors.notes" />
            </div>
            </div>
        </div>
    </form>
</template>
