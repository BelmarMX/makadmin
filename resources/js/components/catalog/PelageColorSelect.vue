<script setup lang="ts">
import BaseCatalogCombobox, { type CatalogOption } from './BaseCatalogCombobox.vue';
import * as catalogRoutes from '@/routes/api/catalog';

const props = defineProps<{
    modelValue?: number | null;
    disabled?: boolean;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: number | null];
    'select': [option: CatalogOption];
}>();
</script>

<template>
    <BaseCatalogCombobox
        :model-value="props.modelValue"
        :endpoint="catalogRoutes.pelageColors().url"
        placeholder="Seleccionar color..."
        :disabled="props.disabled"
        @update:model-value="emit('update:modelValue', $event)"
        @select="emit('select', $event)"
    >
        <template #option="{ option }">
            <span class="flex items-center gap-2">
                <span
                    v-if="option.hex"
                    :style="{ backgroundColor: option.hex as string }"
                    class="inline-block h-4 w-4 rounded-sm border border-border"
                />
                {{ option.name }}
            </span>
        </template>
    </BaseCatalogCombobox>
</template>
