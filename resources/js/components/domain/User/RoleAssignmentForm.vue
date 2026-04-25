<script setup lang="ts">
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    user: { id: number; roles?: Array<{ name: string }> };
    roles: Array<{ value: string; label: string }>;
    action: string;
}>();

const assigned = computed(() => new Set((props.user.roles ?? []).map((role) => role.name)));

const form = useForm({
    roles: props.roles.filter((role) => assigned.value.has(role.value)).map((role) => role.value),
});

function submit() {
    form.put(props.action, { preserveScroll: true });
}
</script>

<template>
    <form class="space-y-4" @submit.prevent="submit">
        <div class="grid gap-3 md:grid-cols-2">
            <label
                v-for="role in roles"
                :key="role.value"
                class="flex items-center gap-3 rounded-lg border p-3 text-sm"
            >
                <input v-model="form.roles" type="checkbox" :value="role.value" class="h-4 w-4 rounded border-input" />
                <span>{{ role.label }}</span>
            </label>
        </div>
        <InputError :message="form.errors.roles" />
        <Button :disabled="form.processing">
            <Save class="h-4 w-4" />
            Guardar roles
        </Button>
    </form>
</template>
