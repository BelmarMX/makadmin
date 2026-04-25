<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from 'lucide-vue-next';
import { ref } from 'vue';
import CropModal from '@/components/CropModal.vue';
import ImageUploadCircle from '@/components/ImageUploadCircle.vue';
import BranchRolesEditor from '@/components/domain/User/BranchRolesEditor.vue';
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
const cropOpen = ref(false);
const cropSrc = ref<string | null>(null);
const avatarPreview = ref<string | null>(null);

type BranchRole = { branch_id: number; roles: string[] };

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
    branch_roles: BranchRole[];
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
    branch_roles: [],
});

function syncRolePayload() {
    form.roles = [...new Set(form.branch_roles.flatMap((assignment) => assignment.roles))];
    form.branch_id = form.branch_roles[0]?.branch_id ? String(form.branch_roles[0].branch_id) : '';
}

function onFileSelected(file: File) {
    cropSrc.value = URL.createObjectURL(file);
    cropOpen.value = true;
}

function onCropConfirm(blob: Blob) {
    cropOpen.value = false;
    form.avatar = new File([blob], 'avatar.webp', { type: 'image/webp' });
    avatarPreview.value = URL.createObjectURL(form.avatar);
}

function removeSelectedAvatar() {
    avatarPreview.value = null;
    form.avatar = null;
}

function submit() {
    syncRolePayload();
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

        <div class="grid gap-4 md:grid-cols-[12rem_1fr]">
            <div class="flex justify-center md:justify-start">
                <ImageUploadCircle
                    :model-value="avatarPreview"
                    size="lg"
                    label="Avatar"
                    :error="form.errors.avatar"
                    @upload="onFileSelected"
                    @remove="removeSelectedAvatar"
                />
                <CropModal
                    :open="cropOpen"
                    :image-src="cropSrc"
                    @confirm="onCropConfirm"
                    @cancel="cropOpen = false"
                    @update:open="cropOpen = $event"
                />
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
                <Label for="password">Contraseña temporal</Label>
                <Input id="password" v-model="form.password" type="password" autocomplete="new-password" />
                <InputError :message="form.errors.password" />
            </div>
            <div class="grid gap-2">
                <Label for="password_confirmation">Confirmar contraseña</Label>
                <Input id="password_confirmation" v-model="form.password_confirmation" type="password" autocomplete="new-password" />
            </div>
            </div>
        </div>

        <BranchRolesEditor v-model="form.branch_roles" :branches="branches" :roles="roles" :error="form.errors.roles || form.errors.branch_roles" />
    </form>
</template>
