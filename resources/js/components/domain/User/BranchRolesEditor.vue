<script setup lang="ts">
import Checkbox from 'primevue/checkbox';
import { BadgeCheck, CircleDollarSign, ConciergeBell, Scissors, ShieldCheck, Stethoscope } from 'lucide-vue-next';
import type { Component } from 'vue';

type Branch = { id: number; name: string };
type RoleOption = { value: string; label: string };
type BranchRole = { branch_id: number; roles: string[] };

const props = defineProps<{
    branches: Branch[];
    roles: RoleOption[];
    modelValue: BranchRole[];
    error?: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: BranchRole[]];
}>();

const roleIcons: Record<string, Component> = {
    clinic_admin: ShieldCheck,
    veterinarian: Stethoscope,
    groomer: Scissors,
    receptionist: ConciergeBell,
    cashier: CircleDollarSign,
};

function roleIcon(role: string): Component {
    return roleIcons[role] ?? BadgeCheck;
}

function branchRoles(branchId: number): string[] {
    return props.modelValue.find((assignment) => assignment.branch_id === branchId)?.roles ?? [];
}

function hasRole(branchId: number, role: string): boolean {
    return branchRoles(branchId).includes(role);
}

function toggleRole(branchId: number, role: string, checked: boolean): void {
    const next = props.modelValue.filter((assignment) => assignment.branch_id !== branchId);
    const roles = checked ? [...branchRoles(branchId), role] : branchRoles(branchId).filter((value) => value !== role);

    if (roles.length > 0) {
        next.push({ branch_id: branchId, roles });
    }

    emit('update:modelValue', next);
}
</script>

<template>
    <section class="space-y-3">
        <div>
            <h2 class="text-base font-semibold text-foreground">Roles por sucursal</h2>
            <p class="text-sm text-muted-foreground">Cada sucursal puede tener una combinación distinta de roles.</p>
        </div>

        <div class="grid gap-3 xl:grid-cols-2">
            <article
                v-for="branch in branches"
                :key="branch.id"
                class="rounded-lg border border-border bg-card p-4"
            >
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-sm font-semibold text-foreground">{{ branch.name }}</h3>
                    <span
                        v-if="branchRoles(branch.id).length"
                        class="rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary"
                    >
                        {{ branchRoles(branch.id).length }} roles
                    </span>
                </div>

                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                    <label
                        v-for="role in roles"
                        :key="`${branch.id}-${role.value}`"
                        class="flex min-h-12 cursor-pointer items-center gap-3 rounded-md border border-border bg-background px-3 py-2 text-sm transition-colors hover:border-primary/50"
                        :class="{ 'border-primary bg-primary/5': hasRole(branch.id, role.value) }"
                    >
                        <Checkbox
                            :model-value="hasRole(branch.id, role.value)"
                            binary
                            @update:model-value="toggleRole(branch.id, role.value, Boolean($event))"
                        />
                        <component :is="roleIcon(role.value)" class="h-4 w-4 shrink-0 text-muted-foreground" />
                        <span class="min-w-0 truncate font-medium text-foreground">{{ role.label }}</span>
                    </label>
                </div>
            </article>
        </div>

        <p v-if="error" class="text-sm text-destructive">{{ error }}</p>
    </section>
</template>
