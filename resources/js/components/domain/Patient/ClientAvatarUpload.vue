<script setup lang="ts">
import { computed, ref } from 'vue';
import { Camera, X } from 'lucide-vue-next';
import CropModal from '@/components/CropModal.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';

const props = defineProps<{
    modelValue?: File | null;
    preview?: string | null;
    name?: string;
    error?: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: File | null];
    'update:preview': [value: string | null];
}>();

const cropOpen = ref(false);
const cropSrc = ref<string | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);

const initials = computed(() =>
    (props.name ?? '')
        .split(' ')
        .filter(Boolean)
        .map((word) => word[0])
        .join('')
        .slice(0, 2)
        .toUpperCase() || 'TT',
);

function onFileSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (!file) {
        return;
    }

    cropSrc.value = URL.createObjectURL(file);
    cropOpen.value = true;
    input.value = '';
}

function onCropConfirm(blob: Blob): void {
    cropOpen.value = false;

    const avatar = new File([blob], 'avatar.webp', { type: 'image/webp' });
    emit('update:modelValue', avatar);
    emit('update:preview', URL.createObjectURL(avatar));
}

function removeAvatar(): void {
    emit('update:modelValue', null);
    emit('update:preview', null);
}
</script>

<template>
    <div class="flex flex-col items-center gap-3">
        <div class="group relative">
            <Avatar class="h-32 w-32 border-2 border-border shadow-sm">
                <AvatarImage v-if="props.preview" :src="props.preview" :alt="props.name ?? 'Avatar del tutor'" />
                <AvatarFallback class="bg-primary/10 text-xl font-semibold text-primary">{{ initials }}</AvatarFallback>
            </Avatar>

            <button
                type="button"
                class="absolute inset-0 flex items-center justify-center rounded-full bg-black/0 transition hover:bg-black/45"
                @click="fileInput?.click()"
            >
                <span class="flex items-center gap-2 rounded-full bg-background/90 px-3 py-1 text-xs font-medium text-foreground opacity-0 transition group-hover:opacity-100">
                    <Camera class="h-4 w-4" />
                    {{ props.preview ? 'Cambiar' : 'Subir' }}
                </span>
            </button>

            <button
                v-if="props.preview"
                type="button"
                class="absolute -right-1 -top-1 flex h-6 w-6 items-center justify-center rounded-full bg-destructive text-white shadow"
                @click="removeAvatar"
            >
                <X class="h-3.5 w-3.5" />
            </button>
        </div>

        <p class="text-center text-xs text-muted-foreground">Avatar del tutor</p>
        <p v-if="props.error" class="text-xs text-destructive">{{ props.error }}</p>

        <input ref="fileInput" type="file" accept="image/*" class="hidden" @change="onFileSelected" />

        <CropModal
            :open="cropOpen"
            :image-src="cropSrc"
            @confirm="onCropConfirm"
            @cancel="cropOpen = false"
            @update:open="cropOpen = $event"
        />
    </div>
</template>
