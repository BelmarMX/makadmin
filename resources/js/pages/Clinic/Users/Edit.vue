<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from 'lucide-vue-next';
import FloatLabel from 'primevue/floatlabel';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import { ref } from 'vue';
import CropModal from '@/components/CropModal.vue';
import ImageUploadCircle from '@/components/ImageUploadCircle.vue';
import BranchRolesEditor from '@/components/domain/User/BranchRolesEditor.vue';
import UserStatusBadge from '@/components/domain/User/UserStatusBadge.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import * as userRoutes from '@/actions/App/Http/Controllers/Clinic/UserController';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    user: {
        id: number;
        name: string;
        email: string;
        phone?: string | null;
        avatar?: string | null;
        professional_license?: string | null;
        branch_id?: number | null;
        is_active: boolean;
        roles?: Array<{ name: string }>;
        branch_roles?: Array<{ branch_id: number; role: string }>;
    };
    branches: Array<{ id: number; name: string }>;
    roles: Array<{ value: string; label: string }>;
}>();

const clinic = window.location.hostname.split('.')[0];
const cropOpen = ref(false);
const cropSrc = ref<string | null>(null);
const avatarPreview = ref<string | null>(props.user.avatar ?? null);

type BranchRole = { branch_id: number; roles: string[] };

function initialBranchRoles(): BranchRole[] {
    const grouped = new Map<number, string[]>();

    for (const assignment of props.user.branch_roles ?? []) {
        grouped.set(assignment.branch_id, [...(grouped.get(assignment.branch_id) ?? []), assignment.role]);
    }

    if (grouped.size === 0 && props.user.branch_id) {
        grouped.set(props.user.branch_id, (props.user.roles ?? []).map((role) => role.name));
    }

    return [...grouped.entries()].map(([branch_id, roles]) => ({ branch_id, roles }));
}

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
    name: props.user.name,
    email: props.user.email,
    phone: props.user.phone ?? '',
    branch_id: props.user.branch_id ? String(props.user.branch_id) : '',
    professional_license: props.user.professional_license ?? '',
    password: '',
    password_confirmation: '',
    avatar: null,
    roles: (props.user.roles ?? []).map((role) => role.name),
    branch_roles: initialBranchRoles(),
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
    form.avatar = null;
    avatarPreview.value = props.user.avatar ?? null;
}

function submit() {
    syncRolePayload();
    form.transform((data) => ({ ...data, _method: 'PUT' })).post(userRoutes.update({ clinic, user: props.user.id }).url, {
        forceFormData: true,
    });
}
</script>

<template>
    <Head :title="`Editar ${user.name}`" />

    <form class="space-y-6" @submit.prevent="submit">
        <div class="flex items-center justify-between gap-4">
            <div>
                <div class="mb-2"><UserStatusBadge :active="user.is_active" /></div>
                <h1 class="text-2xl font-bold text-foreground">Editar usuario</h1>
                <p class="text-sm text-muted-foreground">{{ user.email }}</p>
            </div>
            <div class="flex gap-2">
                <Button variant="outline" as-child>
                    <Link :href="userRoutes.show({ clinic, user: user.id }).url">
                        <ArrowLeft class="h-4 w-4" />
                        Volver
                    </Link>
                </Button>
                <Button :disabled="form.processing" v-ripple>
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
            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                <div class="grid gap-1">
                    <FloatLabel variant="on">
                        <InputText id="name" v-model="form.name" autocomplete="name" class="w-full" />
                        <label for="name">Nombre</label>
                    </FloatLabel>
                    <InputError :message="form.errors.name" />
                </div>
                <div class="grid gap-1">
                    <FloatLabel variant="on">
                        <InputText id="email" v-model="form.email" type="email" autocomplete="email" class="w-full" />
                        <label for="email">Email</label>
                    </FloatLabel>
                    <InputError :message="form.errors.email" />
                </div>
                <div class="grid gap-1">
                    <FloatLabel variant="on">
                        <InputText id="phone" v-model="form.phone" class="w-full" />
                        <label for="phone">Teléfono</label>
                    </FloatLabel>
                    <InputError :message="form.errors.phone" />
                </div>
                <div class="grid gap-1">
                    <FloatLabel variant="on">
                        <InputText id="license" v-model="form.professional_license" class="w-full" />
                        <label for="license">Cédula profesional</label>
                    </FloatLabel>
                    <InputError :message="form.errors.professional_license" />
                </div>
                <div class="grid gap-1">
                    <FloatLabel variant="on">
                        <Password
                            id="password"
                            v-model="form.password"
                            autocomplete="new-password"
                            :feedback="false"
                            toggle-mask
                            class="w-full"
                            input-class="w-full"
                        />
                        <label for="password">Nueva contraseña</label>
                    </FloatLabel>
                    <InputError :message="form.errors.password" />
                </div>
                <div class="grid gap-1">
                    <FloatLabel variant="on">
                        <Password
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            autocomplete="new-password"
                            :feedback="false"
                            toggle-mask
                            class="w-full"
                            input-class="w-full"
                        />
                        <label for="password_confirmation">Confirmar contraseña</label>
                    </FloatLabel>
                </div>
            </div>
        </div>

        <BranchRolesEditor v-model="form.branch_roles" :branches="branches" :roles="roles" :error="form.errors.roles || form.errors.branch_roles" />
    </form>
</template>
