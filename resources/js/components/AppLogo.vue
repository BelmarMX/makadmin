<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import AppLogoName from '@/components/AppLogoName.vue';
</script>

<template>
    <div
        class="flex aspect-square size-8 items-center justify-center rounded-md bg-slate-200"
    >
        <AppLogoIcon class="fill-current text-white dark:text-black" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <AppLogoName />
<!--        <span class="mb-0.5 truncate leading-tight font-semibold"
            >Vetfollow</span
        >-->
    </div>
</template>
