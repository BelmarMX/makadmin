<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Pencil, Power, RotateCcw } from 'lucide-vue-next';
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
    };
    modules: Array<{ module_key: string; label: string }>;
    effectivePermissions: Array<{ id: number; name: string }>;
}>();

const clinic = window.location.hostname.split('.')[0];

function initials(name: string) {
    return name
        .split(' ')
        .filter(Boolean)
        .map((word) => word[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();
}

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
                <Button v-if="user.is_active" variant="destructive" @click="deactivate">
                    <Power class="h-4 w-4" />
                    Desactivar
                </Button>
                <Button v-else @click="restore">
                    <RotateCcw class="h-4 w-4" />
                    Activar
                </Button>
            </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-[1fr_2fr]">
            <Card>
                <CardHeader><CardTitle>Datos</CardTitle></CardHeader>
                <CardContent class="space-y-3 text-sm">
                    <div><span class="text-muted-foreground">Sucursal:</span> {{ user.branch?.name ?? 'Sin sucursal' }}</div>
                    <div><span class="text-muted-foreground">Teléfono:</span> {{ user.phone ?? 'Sin teléfono' }}</div>
                    <div><span class="text-muted-foreground">Cédula:</span> {{ user.professional_license ?? 'No registrada' }}</div>
                    <div class="space-y-2">
                        <span class="text-muted-foreground">Roles:</span>
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="role in user.roles ?? []"
                                :key="role.id"
                                class="rounded-full border border-primary/20 bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary"
                            >
                                {{ roleLabel(role.name) }}
                            </span>
                        </div>
                    </div>
                    <div v-if="user.branch_roles?.length" class="space-y-2">
                        <span class="text-muted-foreground">Sucursales y roles:</span>
                        <div class="space-y-2">
                            <div
                                v-for="assignment in user.branch_roles"
                                :key="assignment.id"
                                class="flex flex-wrap items-center gap-2 rounded-md border bg-muted/20 px-3 py-2"
                            >
                                <span class="font-medium">{{ assignment.branch?.name ?? 'Sucursal' }}</span>
                                <span class="rounded-full bg-background px-2 py-0.5 text-xs text-muted-foreground">{{ roleLabel(assignment.role) }}</span>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader><CardTitle>Permisos directos</CardTitle></CardHeader>
                <CardContent>
                    <PermissionGrid
                        :action="permissionRoutes.update({ clinic, user: user.id }).url"
                        :modules="modules"
                        :permissions="effectivePermissions"
                    />
                </CardContent>
            </Card>
        </div>
    </div>
</template>
