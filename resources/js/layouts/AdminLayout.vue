<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';

const page = usePage();
</script>

<template>
    <div class="min-h-screen bg-background text-foreground">
        <header class="border-b border-border px-6 py-4 flex items-center justify-between">
            <span class="font-semibold text-primary">Makadmin — Admin</span>
            <span class="text-sm text-muted-foreground">{{ page.props.auth?.user?.email }}</span>
        </header>
        <main class="p-6">
            <slot />
        </main>
    </div>
</template>
