<script setup lang="ts">
import { Badge } from '@/components/ui/badge';

defineProps<{
    isActive: boolean;
    isDeceased?: boolean;
}>();
</script>

<template>
    <Badge
        v-if="isDeceased"
        variant="destructive"
    >
        Fallecido
    </Badge>
    <Badge
        v-else-if="isActive"
        class="bg-success text-white"
    >
        Activo
    </Badge>
    <Badge
        v-else
        class="bg-muted text-muted-foreground"
    >
        Inactivo
    </Badge>
</template>
