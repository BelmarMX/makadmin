<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, BadgeCheck, CircleDollarSign, ConciergeBell, GraduationCap, MapPin, Pencil, Phone, Power, RotateCcw, Scissors, ShieldCheck, Stethoscope } from 'lucide-vue-next';
import Chip from 'primevue/chip';
import { computed, ref } from 'vue';
import type { Component } from 'vue';
import PermissionGrid from '@/components/domain/User/PermissionGrid.vue';
import UserStatusBadge from '@/components/domain/User/UserStatusBadge.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { roleLabel } from '@/lib/userLabels';
import * as permissionRoutes from '@/actions/App/Http/Controllers/Clinic/UserPermissionController';
import * as userRoutes from '@/actions/App/Http/Controllers/Clinic/UserController';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    user: {
        id: number;
        name: string;
        email: string;
        phone?: string | null;
        avatar?: string | null;
        professional_license?: string | null;
        is_active: boolean;
        branch?: { id: number; name: string } | null;
        roles?: Array<{ id: number; name: string }>;
        permissions?: Array<{ id: number; name: string }>;
        branch_roles?: Array<{ id: number; branch_id: number; role: string; branch?: { id: number; name: string } | null }>;
        user_branch_permissions?: Array<{ branch_id: number; permission: string }>;
    };
    modules: Array<{ module_key: string; label: string; description?: string }>;
    effectivePermissions: Array<{ id: number; name: string }>;
}>();

const clinic = window.location.hostname.split('.')[0];

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

function initials(name: string) {
    return name
        .split(' ')
        .filter(Boolean)
        .map((word) => word[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();
}

type BranchGroup = { branch: { id: number; name: string }; roles: string[] };

const branchGroups = computed((): BranchGroup[] => {
    const map = new Map<number, BranchGroup>();

    for (const branchRole of props.user.branch_roles ?? []) {
        if (!branchRole.branch) {
            continue;
        }

        const existing = map.get(branchRole.branch_id);

        if (existing) {
            existing.roles.push(branchRole.role);
        } else {
            map.set(branchRole.branch_id, { branch: branchRole.branch, roles: [branchRole.role] });
        }
    }

    return [...map.values()];
});

const primaryBranchId = computed((): number | null => props.user.branch?.id ?? props.user.branch_roles?.[0]?.branch_id ?? null);
const selectedBranchId = ref<number | null>(primaryBranchId.value);

const selectedBranchPermissions = computed(() => {
    if (!selectedBranchId.value) {
        return props.effectivePermissions;
    }

    return (props.user.user_branch_permissions ?? [])
        .filter((permission) => permission.branch_id === selectedBranchId.value)
        .map((permission, index) => ({ id: index, name: permission.permission }));
});

const permissionActionUrl = computed(() => {
    const base = permissionRoutes.update({ clinic, user: props.user.id }).url;

    return selectedBranchId.value ? `${base}?branch_id=${selectedBranchId.value}` : base;
});

function deactivate() {
    router.post(userRoutes.deactivate({ clinic, user: props.user.id }).url, {}, { preserveScroll: true });
}

function restore() {
    router.post(userRoutes.restore({ clinic, user: props.user.id }).url, {}, { preserveScroll: true });
}
</script>

<template>
    <Head :title="user.name" />

    <div class="space-y-6">
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <Avatar class="h-14 w-14">
                    <AvatarImage v-if="user.avatar" :src="user.avatar" :alt="user.name" />
                    <AvatarFallback>{{ initials(user.name) }}</AvatarFallback>
                </Avatar>
                <div>
                    <div class="mb-2"><UserStatusBadge :active="user.is_active" /></div>
                    <h1 class="text-2xl font-bold text-foreground">{{ user.name }}</h1>
                    <p class="text-sm text-muted-foreground">{{ user.email }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                <Button variant="outline" as-child>
                    <Link :href="userRoutes.index(clinic).url">
                        <ArrowLeft class="h-4 w-4" />
                        Volver
                    </Link>
                </Button>
                <Button variant="outline" as-child>
                    <Link :href="userRoutes.edit({ clinic, user: user.id }).url">
                        <Pencil class="h-4 w-4" />
                        Editar
                    </Link>
                </Button>
                <Button v-if="user.is_active" variant="destructive" v-ripple @click="deactivate">
                    <Power class="h-4 w-4" />
                    Desactivar
                </Button>
                <Button v-else v-ripple @click="restore">
                    <RotateCcw class="h-4 w-4" />
                    Activar
                </Button>
            </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-[1fr_2fr]">
            <Card>
                <CardHeader><CardTitle>Datos</CardTitle></CardHeader>
                <CardContent class="space-y-4 text-sm">
                    <div v-if="user.phone" class="flex items-center gap-2">
                        <Phone class="h-4 w-4 shrink-0 text-muted-foreground" />
                        <span>{{ user.phone }}</span>
                    </div>
                    <div v-if="user.professional_license" class="flex items-center gap-2">
                        <GraduationCap class="h-4 w-4 shrink-0 text-muted-foreground" />
                        <span>{{ user.professional_license }}</span>
                    </div>

                    <div class="space-y-3 pt-1">
                        <button
                            v-for="group in branchGroups"
                            :key="group.branch.id"
                            type="button"
                            class="w-full space-y-2 rounded-lg border px-3 py-2.5 text-left transition-colors hover:border-primary/50"
                            :class="selectedBranchId === group.branch.id ? 'border-primary bg-primary/5' : 'border-border bg-muted/20'"
                            @click="selectedBranchId = group.branch.id"
                        >
                            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                <MapPin class="h-3.5 w-3.5 shrink-0" />
                                <span class="truncate">{{ group.branch.name }}</span>
                            </div>
                            <div class="flex flex-wrap gap-1 pl-5">
                                <Chip v-for="role in group.roles" :key="role" class="!px-2 !py-0.5 !text-xs">
                                    <template #default>
                                        <component :is="roleIcon(role)" class="h-3 w-3 shrink-0" />
                                        <span class="ml-1 text-xs">{{ roleLabel(role) }}</span>
                                    </template>
                                </Chip>
                            </div>
                        </button>
                        <p v-if="branchGroups.length === 0" class="text-muted-foreground">Sin sucursal asignada</p>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>
                        Permisos directos
                        <span v-if="selectedBranchId" class="ml-2 text-sm font-normal text-muted-foreground">
                            — {{ branchGroups.find((group) => group.branch.id === selectedBranchId)?.branch.name }}
                        </span>
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <PermissionGrid
                        :action="permissionActionUrl"
                        :modules="modules"
                        :permissions="selectedBranchPermissions"
                        :branch-id="selectedBranchId"
                    />
                </CardContent>
            </Card>
        </div>
    </div>
</template>
