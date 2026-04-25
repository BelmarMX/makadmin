<script setup lang="ts">
import { Form, Head, Link, router, usePage } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import CropModal from '@/components/CropModal.vue';
import Heading from '@/components/Heading.vue';
import ImageUploadCircle from '@/components/ImageUploadCircle.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';

type Props = {
    mustVerifyEmail: boolean;
    status?: string;
};

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Perfil',
                href: edit(),
            },
        ],
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const cropOpen = ref(false);
const cropSrc = ref<string | null>(null);

function onFileSelected(file: File) {
    cropSrc.value = URL.createObjectURL(file);
    cropOpen.value = true;
}

function onCropConfirm(blob: Blob) {
    cropOpen.value = false;

    const fd = new FormData();
    fd.append('image', new File([blob], 'avatar.webp', { type: 'image/webp' }));

    router.post('/settings/profile/avatar', fd, {
        forceFormData: true,
        preserveScroll: true,
    });
}

function removeAvatar() {
    router.delete('/settings/profile/avatar', {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Configuración de perfil" />

    <h1 class="sr-only">Configuración de perfil</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Información del usuario"
            description="Actualiza tu avatar, nombre y dirección de correo electrónico"
        />

        <Form
            v-bind="ProfileController.update.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="flex justify-center pb-4">
                <ImageUploadCircle
                    :model-value="user?.avatar ?? null"
                    size="lg"
                    label="Foto de perfil"
                    @upload="onFileSelected"
                    @remove="removeAvatar"
                />
                <CropModal
                    :open="cropOpen"
                    :image-src="cropSrc"
                    @confirm="onCropConfirm"
                    @cancel="cropOpen = false"
                    @update:open="cropOpen = $event"
                />
            </div>

            <div class="grid gap-2">
                <Label for="name">Nombre</Label>
                <Input
                    id="name"
                    class="mt-1 block w-full"
                    name="name"
                    :default-value="user.name"
                    required
                    autocomplete="name"
                    placeholder="Nombre completo"
                />
                <InputError class="mt-2" :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Dirección de correo</Label>
                <Input
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    name="email"
                    :default-value="user.email"
                    required
                    autocomplete="username"
                    placeholder="Dirección de correo electrónico"
                />
                <InputError class="mt-2" :message="errors.email" />
            </div>

            <div v-if="mustVerifyEmail && !user.email_verified_at">
                <p class="-mt-4 text-sm text-muted-foreground">
                    Tu dirección de correo electrónico no ha sido verificada.
                    <Link
                        :href="send()"
                        as="button"
                        class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                    >
                        Reenviar verificación de correo electrónico.
                    </Link>
                </p>

                <div
                    v-if="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600"
                >
                    Un nuevo enlace de verificación se envió a tu dirección de
                    correo electrónico.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <Button
                    :disabled="processing"
                    data-test="update-profile-button"
                >
                    <Save class="-ml-1 h-4 w-4" />
                    Guardar
                </Button>
            </div>
        </Form>
    </div>
</template>
