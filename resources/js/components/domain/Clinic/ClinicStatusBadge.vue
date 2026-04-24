<script setup lang="ts">
import { Badge } from '@/components/ui/badge';

const props = defineProps<{ isActive: boolean; deletedAt?: string | null }>();
</script>

<template>
    <Badge v-if="props.deletedAt" variant="outline" class="text-muted-foreground">Archivada</Badge>
    <Badge v-else-if="props.isActive" class="bg-success text-white">Activa</Badge>
    <Badge v-else variant="secondary">Inactiva</Badge>
</template>
