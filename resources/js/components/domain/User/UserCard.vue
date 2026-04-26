<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { BadgeCheck, CircleDollarSign, ConciergeBell, Mail, MapPin, Pencil, Scissors, ShieldCheck, Stethoscope } from 'lucide-vue-next';
import Chip from 'primevue/chip';
import type { Component } from 'vue';
import UserStatusBadge from '@/components/domain/User/UserStatusBadge.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import * as userRoutes from '@/actions/App/Http/Controllers/Clinic/UserController';
import { roleLabel } from '@/lib/userLabels';

defineProps<{
    user: {
        id: number;
        name: string;
        email: string;
        phone?: string | null;
        avatar?: string | null;
        is_active: boolean;
        branch_roles: Array<{ branch_id: number; role: string; branch: { id: number; name: string } | null }>;
    };
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

function groupedBranches(branchRoles: Array<{ branch_id: number; role: string; branch: { id: number; name: string } | null }>): BranchGroup[] {
    const map = new Map<number, BranchGroup>();

    for (const branchRole of branchRoles) {
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
}
</script>

<template>
    <Card>
        <CardContent class="flex items-start gap-4 p-4">
            <Avatar class="h-12 w-12 shrink-0">
                <AvatarImage v-if="user.avatar" :src="user.avatar" :alt="user.name" />
                <AvatarFallback>{{ initials(user.name) }}</AvatarFallback>
            </Avatar>

            <div class="min-w-0 flex-1 space-y-2">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <Link :href="userRoutes.show({ clinic, user: user.id }).url" class="font-medium text-foreground hover:underline">
                            {{ user.name }}
                        </Link>
                        <div class="mt-1 flex items-center gap-2 text-sm text-muted-foreground">
                            <Mail class="h-3.5 w-3.5 shrink-0" />
                            <span class="truncate">{{ user.email }}</span>
                        </div>
                    </div>
                    <UserStatusBadge :active="user.is_active" />
                </div>

                <div class="space-y-1.5">
                    <div v-for="group in groupedBranches(user.branch_roles)" :key="group.branch.id" class="space-y-1">
                        <div class="flex items-center gap-1 text-xs font-medium text-muted-foreground">
                            <MapPin class="h-3.5 w-3.5 shrink-0" />
                            <span class="truncate uppercase tracking-wide">{{ group.branch.name }}</span>
                        </div>
                        <div class="flex flex-wrap gap-1 pl-4">
                            <Chip v-for="role in group.roles" :key="role" :label="roleLabel(role)" class="!px-2 !py-0.5 !text-xs">
                                <template #default>
                                    <component :is="roleIcon(role)" class="h-3 w-3 shrink-0" />
                                    <span class="ml-1 text-xs">{{ roleLabel(role) }}</span>
                                </template>
                            </Chip>
                        </div>
                    </div>
                    <p v-if="groupedBranches(user.branch_roles).length === 0" class="text-xs text-muted-foreground">Sin sucursal asignada</p>
                </div>
            </div>

            <Button variant="ghost" size="icon" as-child>
                <Link :href="userRoutes.edit({ clinic, user: user.id }).url" title="Editar usuario">
                    <Pencil class="h-4 w-4" />
                </Link>
            </Button>
        </CardContent>
    </Card>
</template>
