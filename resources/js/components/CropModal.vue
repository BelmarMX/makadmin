<script setup lang="ts">
import { ref } from 'vue';
import { Cropper, CircleStencil } from 'vue-advanced-cropper';
import 'vue-advanced-cropper/dist/style.css';
import { CropIcon, X } from 'lucide-vue-next';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    open: boolean;
    imageSrc: string | null;
}>();

const emit = defineEmits<{
    confirm: [blob: Blob];
    cancel: [];
    'update:open': [value: boolean];
}>();

const cropperRef = ref<InstanceType<typeof Cropper> | null>(null);

function confirm() {
    const canvas = cropperRef.value?.getResult()?.canvas;
    if (!canvas) return;

    canvas.toBlob(
        (blob) => {
            if (blob) emit('confirm', blob);
        },
        'image/webp',
        0.9,
    );
}
</script>

<template>
    <Dialog :open="props.open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>Recortar imagen</DialogTitle>
            </DialogHeader>

            <div class="flex items-center justify-center overflow-hidden rounded-lg bg-muted" style="height: 320px">
                <Cropper
                    v-if="props.imageSrc"
                    ref="cropperRef"
                    :src="props.imageSrc"
                    :stencil-component="CircleStencil"
                    :stencil-props="{ aspectRatio: 1 }"
                    class="h-full w-full"
                />
            </div>

            <DialogFooter class="gap-2">
                <Button variant="outline" @click="emit('cancel')">
                    <X class="h-4 w-4" />
                    Cancelar
                </Button>
                <Button @click="confirm">
                    <CropIcon class="h-4 w-4" />
                    Confirmar
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
