<script setup lang="ts">
import { ref } from 'vue';
import CropModal from '@/components/CropModal.vue';
import ImageUploadCircle from '@/components/ImageUploadCircle.vue';

const props = defineProps<{
    modelValue?: File | null;
    preview?: string | null;
    error?: string;
    label?: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: File | null];
    'update:preview': [value: string | null];
}>();

const cropOpen = ref(false);
const cropSrc = ref<string | null>(null);

function onFileSelected(file: File): void {
    cropSrc.value = URL.createObjectURL(file);
    cropOpen.value = true;
}

function onCropConfirm(blob: Blob): void {
    cropOpen.value = false;

    const file = new File([blob], 'patient-photo.webp', { type: 'image/webp' });

    emit('update:modelValue', file);
    emit('update:preview', URL.createObjectURL(file));
}

function removeSelectedPhoto(): void {
    emit('update:modelValue', null);
    emit('update:preview', null);
}
</script>

<template>
    <div class="flex justify-center md:justify-start">
        <ImageUploadCircle
            :model-value="props.preview"
            size="lg"
            :label="props.label ?? 'Foto'"
            :error="props.error"
            @upload="onFileSelected"
            @remove="removeSelectedPhoto"
        />
        <CropModal
            :open="cropOpen"
            :image-src="cropSrc"
            @confirm="onCropConfirm"
            @cancel="cropOpen = false"
            @update:open="cropOpen = $event"
        />
    </div>
</template>
