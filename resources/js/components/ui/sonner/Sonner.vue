<script setup lang="ts">
import { useAppearance } from '@/composables/useAppearance';
import { Toaster as SonnerPrimitive } from 'vue-sonner';
import 'vue-sonner/style.css';

const { appearance } = useAppearance();
</script>

<template>
    <SonnerPrimitive
        :theme="appearance"
        class="toaster group"
        position="bottom-right"
        close-button
        rich-colors
        :style="{
            '--normal-bg': 'var(--popover)',
            '--normal-text': 'var(--popover-foreground)',
            '--normal-border': 'var(--border)',
            '--success-bg': 'hsl(var(--success))',
            '--success-text': 'white',
            '--success-border': 'hsl(var(--success))',
            '--error-bg': 'hsl(var(--destructive))',
            '--error-text': 'white',
            '--error-border': 'hsl(var(--destructive))',
            '--warning-bg': 'hsl(var(--warning))',
            '--warning-text': 'black',
            '--warning-border': 'hsl(var(--warning))',
            '--info-bg': 'hsl(var(--primary))',
            '--info-text': 'white',
            '--info-border': 'hsl(var(--primary))',
        }"
    />
</template>
