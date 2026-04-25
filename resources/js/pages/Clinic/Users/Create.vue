<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import * as userRoutes from '@/actions/App/Http/Controllers/Clinic/UserController';

defineOptions({ layout: AppLayout });

defineProps<{
    branches: Array<{ id: number; name: string }>;
    roles: Array<{ value: string; label: string }>;
}>();

const clinic = window.location.hostname.split('.')[0];
const form = useForm<{
    name: string;
    email: string;
    phone: string;
    branch_id: string;
    professional_license: string;
    password: string;
    password_confirmation: string;
    avatar: File | null;
    roles: string[];
}>({
    name: '',
    email: '',
    phone: '',
    branch_id: '',
    professional_license: '',
    password: '',
    password_confirmation: '',
    avatar: null,
    roles: [],
});

function submit() {
    form.post(userRoutes.store(clinic).url, { forceFormData: true });
}
</script>

<template>
    <Head title="Nuevo usuario" />

    <form class="space-y-6" @submit.prevent="submit">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Nuevo usuario</h1>
                <p class="text-sm text-muted-foreground">Alta de integrante con roles de clínica.</p>
            </div>
            <div class="flex gap-2">
                <Button variant="outline" as-child>
                    <Link :href="userRoutes.index(clinic).url">
                        <ArrowLeft class="h-4 w-4" />
                        Volver
                    </Link>
                </Button>
                <Button :disabled="form.processing">
                    <Save class="h-4 w-4" />
                    Guardar
                </Button>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div class="grid gap-2">
                <Label for="name">Nombre</Label>
                <Input id="name" v-model="form.name" autocomplete="name" />
                <InputError :message="form.errors.name" />
            </div>
            <div class="grid gap-2">
                <Label for="email">Email</Label>
                <Input id="email" v-model="form.email" type="email" autocomplete="email" />
                <InputError :message="form.errors.email" />
            </div>
            <div class="grid gap-2">
                <Label for="phone">Teléfono</Label>
                <Input id="phone" v-model="form.phone" />
                <InputError :message="form.errors.phone" />
            </div>
            <div class="grid gap-2">
                <Label for="branch">Sucursal</Label>
                <select id="branch" v-model="form.branch_id" class="h-9 rounded-md border border-input bg-background px-3 text-sm">
                    <option value="">Selecciona sucursal</option>
                    <option v-for="branch in branches" :key="branch.id" :value="branch.id">{{ branch.name }}</option>
                </select>
                <InputError :message="form.errors.branch_id" />
            </div>
            <div class="grid gap-2">
                <Label for="license">Cédula profesional</Label>
                <Input id="license" v-model="form.professional_license" />
                <InputError :message="form.errors.professional_license" />
            </div>
            <div class="grid gap-2">
                <Label for="avatar">Avatar</Label>
                <Input id="avatar" type="file" accept="image/png,image/jpeg,image/webp" @input="form.avatar = ($event.target as HTMLInputElement).files?.[0] ?? null" />
                <InputError :message="form.errors.avatar" />
            </div>
            <div class="grid gap-2">
                <Label for="password">Contraseña temporal</Label>
                <Input id="password" v-model="form.password" type="password" autocomplete="new-password" />
                <InputError :message="form.errors.password" />
            </div>
            <div class="grid gap-2">
                <Label for="password_confirmation">Confirmar contraseña</Label>
                <Input id="password_confirmation" v-model="form.password_confirmation" type="password" autocomplete="new-password" />
            </div>
        </div>

        <section class="space-y-3">
            <h2 class="text-base font-semibold">Roles</h2>
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                <label v-for="role in roles" :key="role.value" class="flex items-center gap-3 rounded-lg border p-3 text-sm">
                    <input v-model="form.roles" type="checkbox" :value="role.value" class="h-4 w-4 rounded border-input" />
                    <span>{{ role.label }}</span>
                </label>
            </div>
            <InputError :message="form.errors.roles" />
        </section>
    </form>
</template>
