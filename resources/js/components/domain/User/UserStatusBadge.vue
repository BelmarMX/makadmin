<script setup lang="ts">
import { Badge } from '@/components/ui/badge';

defineProps<{
    active: boolean;
}>();
</script>

<template>
    <Badge v-if="active" class="bg-success text-white">
        Activo
    </Badge>
    <Badge v-else variant="destructive">
        Inactivo
    </Badge>
</template>
