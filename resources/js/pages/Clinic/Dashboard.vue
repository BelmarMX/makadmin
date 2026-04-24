<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

defineProps<{
    clinic: { id: number; commercial_name: string; slug: string };
}>();
</script>

<template>
    <div>
        <h1 class="text-2xl font-bold text-foreground">{{ clinic.commercial_name }}</h1>
        <p class="mt-2 text-muted-foreground">Dashboard de clínica.</p>
    </div>
</template>
