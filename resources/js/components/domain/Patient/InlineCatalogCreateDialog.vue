<script setup lang="ts">
import { ref } from 'vue';
import { Plus, Save } from 'lucide-vue-next';
import FloatLabel from 'primevue/floatlabel';
import InputText from 'primevue/inputtext';
import { toast } from '@/lib/toast';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';

const props = withDefaults(defineProps<{
    title: string;
    description?: string;
    endpoint: string;
    payload?: Record<string, unknown>;
    nameLabel?: string;
    hexLabel?: string;
    showHex?: boolean;
    buttonLabel?: string;
    disabled?: boolean;
}>(), {
    description: undefined,
    payload: () => ({}),
    nameLabel: 'Nombre',
    hexLabel: 'Color HEX',
    showHex: false,
    buttonLabel: 'Agregar',
    disabled: false,
});

const emit = defineEmits<{
    saved: [value: Record<string, unknown>];
}>();

const open = ref(false);
const processing = ref(false);
const name = ref('');
const hex = ref('');
const errors = ref<Record<string, string>>({});

async function submit(): Promise<void> {
    processing.value = true;
    errors.value = {};

    try {
        const token = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
        const response = await fetch(props.endpoint, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                ...props.payload,
                name: name.value,
                hex: props.showHex ? hex.value : undefined,
            }),
        });

        const json = await response.json();

        if (!response.ok) {
            errors.value = json.errors ?? {};
            toast.error('No se pudo guardar');
            return;
        }

        emit('saved', json.data ?? json);
        open.value = false;
        name.value = '';
        hex.value = '';
        toast.success('Registro agregado');
    } catch {
        toast.error('No se pudo guardar');
    } finally {
        processing.value = false;
    }
}
</script>

<template>
    <Button
        type="button"
        variant="outline"
        size="icon"
        :disabled="props.disabled"
        v-ripple
        v-tooltip.bottom="props.buttonLabel"
        @click="open = true"
    >
        <Plus class="h-4 w-4" />
    </Button>

    <Dialog :open="open" @update:open="open = $event">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ props.title }}</DialogTitle>
                <p v-if="props.description" class="text-sm text-muted-foreground">{{ props.description }}</p>
            </DialogHeader>

            <div class="grid gap-4 py-2">
                <div class="grid gap-1">
                    <FloatLabel variant="on">
                        <InputText id="inline-catalog-name" v-model="name" class="w-full" />
                        <label for="inline-catalog-name">{{ props.nameLabel }}</label>
                    </FloatLabel>
                    <InputError :message="errors.name" />
                </div>

                <div v-if="props.showHex" class="grid gap-1">
                    <FloatLabel variant="on">
                        <InputText id="inline-catalog-hex" v-model="hex" class="w-full" />
                        <label for="inline-catalog-hex">{{ props.hexLabel }}</label>
                    </FloatLabel>
                    <InputError :message="errors.hex" />
                </div>
            </div>

            <DialogFooter>
                <Button type="button" variant="outline" @click="open = false">Cancelar</Button>
                <Button type="button" :disabled="processing" v-ripple @click="submit">
                    <Save class="h-4 w-4" />
                    Guardar
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
