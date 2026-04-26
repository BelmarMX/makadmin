<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { clinicSlug } from '@/composables/useClinicSlug';
import * as profileRoutes from '@/actions/App/Http/Controllers/Clinic/ProfileController';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    user: { name: string; email: string; phone?: string | null };
}>();

const clinic = clinicSlug();
const form = useForm({
    name: props.user.name,
    phone: props.user.phone ?? '',
    password: '',
    password_confirmation: '',
    avatar: null as File | null,
});

function submit() {
    form.put(profileRoutes.update(clinic).url, { forceFormData: true, preserveScroll: true });
}
</script>

<template>
    <Head title="Perfil" />

    <form class="space-y-6" @submit.prevent="submit">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Perfil</h1>
                <p class="text-sm text-muted-foreground">{{ user.email }}</p>
            </div>
            <Button :disabled="form.processing">
                <Save class="h-4 w-4" />
                Guardar
            </Button>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div class="grid gap-2">
                <Label for="name">Nombre</Label>
                <Input id="name" v-model="form.name" autocomplete="name" />
                <InputError :message="form.errors.name" />
            </div>
            <div class="grid gap-2">
                <Label for="phone">Teléfono</Label>
                <Input id="phone" v-model="form.phone" />
                <InputError :message="form.errors.phone" />
            </div>
            <div class="grid gap-2">
                <Label for="avatar">Avatar</Label>
                <Input id="avatar" type="file" accept="image/png,image/jpeg,image/webp" @input="form.avatar = ($event.target as HTMLInputElement).files?.[0] ?? null" />
                <InputError :message="form.errors.avatar" />
            </div>
            <div class="grid gap-2">
                <Label for="password">Nueva contraseña</Label>
                <Input id="password" v-model="form.password" type="password" autocomplete="new-password" />
                <InputError :message="form.errors.password" />
            </div>
            <div class="grid gap-2">
                <Label for="password_confirmation">Confirmar contraseña</Label>
                <Input id="password_confirmation" v-model="form.password_confirmation" type="password" autocomplete="new-password" />
            </div>
        </div>
    </form>
</template>
