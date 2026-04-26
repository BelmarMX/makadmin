<script setup lang="ts">
import { cn } from '@/lib/utils';

const props = defineProps<{
    class?: string;
    modelValue?: string;
    placeholder?: string;
    disabled?: boolean;
    rows?: number;
}>();

defineEmits<{ 'update:modelValue': [value: string] }>();
</script>

<template>
    <textarea
        :value="props.modelValue"
        :placeholder="props.placeholder"
        :disabled="props.disabled"
        :rows="props.rows ?? 3"
        :class="
            cn(
                'flex min-h-[60px] w-full rounded-md border border-input dark:border-primary/25 bg-transparent px-3 py-2 text-base shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
                props.class,
            )
        "
        @input="$emit('update:modelValue', ($event.target as HTMLTextAreaElement).value)"
    />
</template>
