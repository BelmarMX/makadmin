<script setup lang="ts">
import { ref } from 'vue';
import { Camera, ImageIcon, X } from 'lucide-vue-next';

const props = withDefaults(
    defineProps<{
        modelValue?: string | null;
        size?: 'sm' | 'md' | 'lg';
        disabled?: boolean;
        label?: string;
        error?: string;
    }>(),
    {
        modelValue: null,
        size: 'md',
        disabled: false,
        label: undefined,
        error: undefined,
    },
);

const emit = defineEmits<{
    upload: [file: File];
    remove: [];
}>();

const sizeMap = {
    sm: 'h-16 w-16',
    md: 'h-24 w-24',
    lg: 'h-32 w-32',
};

const fileInput = ref<HTMLInputElement | null>(null);
const isDragging = ref(false);

function onFileChange(e: Event) {
    const input = e.target as HTMLInputElement;
    const file = input.files?.[0];
    if (file) emit('upload', file);
    if (input) input.value = '';
}

function onDrop(e: DragEvent) {
    isDragging.value = false;
    const file = e.dataTransfer?.files[0];
    if (file && file.type.startsWith('image/')) emit('upload', file);
}

function onDragOver(e: DragEvent) {
    e.preventDefault();
    isDragging.value = true;
}
</script>

<template>
    <div class="flex flex-col items-center gap-2">
        <div class="relative">
            <div
                :class="[
                    sizeMap[props.size],
                    'group relative cursor-pointer overflow-hidden rounded-full border-2 border-border bg-muted',
                    isDragging && 'border-primary',
                    props.disabled && 'pointer-events-none opacity-50',
                ]"
                @click="fileInput?.click()"
                @dragover="onDragOver"
                @dragleave="isDragging = false"
                @drop.prevent="onDrop"
            >
                <img v-if="props.modelValue" :src="props.modelValue" alt="Imagen" class="h-full w-full object-cover" />
                <div v-else class="flex h-full w-full items-center justify-center">
                    <ImageIcon class="h-1/3 w-1/3 text-muted-foreground" />
                </div>

                <div
                    class="absolute inset-0 flex flex-col items-center justify-center gap-1 rounded-full bg-black/50 opacity-0 transition-opacity group-hover:opacity-100"
                >
                    <Camera class="h-5 w-5 text-white" />
                    <span class="text-xs font-medium text-white">{{ props.modelValue ? 'Cambiar' : 'Subir' }}</span>
                </div>
            </div>

            <button
                v-if="props.modelValue && !props.disabled"
                type="button"
                class="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-destructive text-white shadow hover:bg-destructive/90"
                @click.stop="emit('remove')"
            >
                <X class="h-3 w-3" />
            </button>
        </div>

        <span v-if="props.label" class="text-xs text-muted-foreground">{{ props.label }}</span>
        <p v-if="props.error" class="text-xs text-destructive">{{ props.error }}</p>

        <input ref="fileInput" type="file" accept="image/*" class="hidden" @change="onFileChange" />
    </div>
</template>
