<script setup lang="ts">
import BaseCatalogCombobox, { type CatalogOption } from './BaseCatalogCombobox.vue';
import * as catalogRoutes from '@/routes/api/catalog';

const props = defineProps<{
    modelValue?: number | null;
    stateId?: number | null;
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
        :endpoint="catalogRoutes.municipalities().url"
        :parent-id="props.stateId"
        placeholder="Seleccionar municipio..."
        :disabled="props.disabled || !props.stateId"
        @update:model-value="emit('update:modelValue', $event)"
        @select="emit('select', $event)"
    />
</template>
