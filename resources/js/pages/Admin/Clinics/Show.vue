<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    AlertCircle,
    BadgeCheck,
    CircleDollarSign,
    ConciergeBell,
    ExternalLink,
    ImageIcon,
    Pencil,
    Plus,
    Power,
    RotateCcw,
    Save,
    Scissors,
    ShieldCheck,
    Stethoscope,
    Trash2,
    UserPlus,
} from 'lucide-vue-next';
import Checkbox from 'primevue/checkbox';
import Chip from 'primevue/chip';
import { computed, onMounted, ref, watch } from 'vue';
import type { Component } from 'vue';
import ClinicStatusBadge from '@/components/domain/Clinic/ClinicStatusBadge.vue';
import BranchListItem from '@/components/domain/Clinic/BranchListItem.vue';
import ModuleToggleCard from '@/components/domain/Clinic/ModuleToggleCard.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import * as adminRoutes from '@/actions/App/Http/Controllers/Admin/ClinicAdminController';
import * as branchRoutes from '@/actions/App/Http/Controllers/Admin/ClinicBranchController';
import * as clinicRoutes from '@/actions/App/Http/Controllers/Admin/ClinicController';
import * as clinicUserRoutes from '@/actions/App/Http/Controllers/Admin/ClinicUserController';
import * as roleModuleRoutes from '@/actions/App/Http/Controllers/Admin/ClinicRoleModuleController';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { roleLabel } from '@/lib/userLabels';

defineOptions({ layout: AdminLayout });

const page = usePage();
const isSuperAdmin = computed(() => (page.props.auth?.user as { is_super_admin?: boolean })?.is_super_admin === true);

const props = defineProps<{
    clinic: {
        id: number;
        slug: string;
        commercial_name: string;
        legal_name: string;
        contact_email: string;
        contact_phone: string;
        responsible_vet_name: string;
        responsible_vet_license: string;
        is_active: boolean;
        logo_url?: string | null;
        deleted_at?: string | null;
        subdomain_url: string;
        branches: Array<{ id: number; name: string; address: string; phone?: string | null; is_main: boolean; is_active: boolean }>;
        users: Array<{
            id: number;
            name: string;
            email: string;
            phone?: string | null;
            is_active: boolean;
            email_verified_at: string | null;
            roles?: Array<{ name: string }>;
        }>;
        roleModuleConfig: Array<{ role: string; module_key: string; is_enabled: boolean }>;
    };
    modules: Array<{ key: string; label: string; description: string; icon: string; dependsOn: string[]; active: boolean }>;
}>();

const roleIcons: Record<string, Component> = {
    clinic_admin: ShieldCheck,
    veterinarian: Stethoscope,
    groomer: Scissors,
    receptionist: ConciergeBell,
    cashier: CircleDollarSign,
};

const availableRoles = [
    { value: 'clinic_admin', label: 'Administrador de clínica' },
    { value: 'veterinarian', label: 'Veterinario' },
    { value: 'groomer', label: 'Esteticista' },
    { value: 'receptionist', label: 'Recepcionista' },
    { value: 'cashier', label: 'Cajero' },
];

const roleModuleState = ref<Record<string, Record<string, boolean>>>({});
const editingUserId = ref<number | null>(null);

type EditForm = { name: string; email: string; phone: string };
const editForms = ref<Record<number, EditForm>>({});

const showBranchForm = ref(false);
const branchForm = useForm({ name: '', address: '', phone: '' });

const showInviteForm = ref(false);
const inviteForm = useForm({ name: '', email: '', phone: '' });

watch(editingUserId, (id) => {
    if (id !== null) {
        const user = props.clinic.users.find((clinicUser) => clinicUser.id === id);

        if (user) {
            editForms.value[id] = {
                name: user.name,
                email: user.email,
                phone: user.phone ?? '',
            };
        }
    }
});

function storeBranch() {
    branchForm.post(branchRoutes.store({ clinic: props.clinic.id }).url, {
        onSuccess: () => {
            showBranchForm.value = false;
            branchForm.reset();
        },
        preserveScroll: true,
    });
}

function toggleActive() {
    const url = props.clinic.is_active
        ? clinicRoutes.deactivate({ clinic: props.clinic.id }).url
        : clinicRoutes.activate({ clinic: props.clinic.id }).url;

    router.post(url, {}, { preserveScroll: true });
}

function inviteUser() {
    inviteForm.post(adminRoutes.invite({ clinic: props.clinic.id }).url, {
        preserveScroll: true,
        onSuccess: () => {
            showInviteForm.value = false;
            inviteForm.reset();
        },
    });
}

function verifyEmail(userId: number) {
    router.post(
        adminRoutes.verifyEmail({ clinic: props.clinic.id, user: userId }).url,
        {},
        { preserveScroll: true },
    );
}

function saveUser(userId: number) {
    router.put(
        clinicUserRoutes.update({ clinic: props.clinic.id, user: userId }).url,
        editForms.value[userId],
        {
            preserveScroll: true,
            onSuccess: () => {
                editingUserId.value = null;
            },
        },
    );
}

function suspendUser(userId: number) {
    router.post(
        clinicUserRoutes.deactivate({ clinic: props.clinic.id, user: userId }).url,
        {},
        { preserveScroll: true },
    );
}

function activateUser(userId: number) {
    router.post(
        clinicUserRoutes.activate({ clinic: props.clinic.id, user: userId }).url,
        {},
        { preserveScroll: true },
    );
}

function deleteUser(userId: number, name: string) {
    if (!confirm(`¿Eliminar a ${name}? Esta acción no se puede deshacer.`)) {
        return;
    }

    router.delete(clinicUserRoutes.destroy({ clinic: props.clinic.id, user: userId }).url, {
        preserveScroll: true,
    });
}

function initRoleModuleState() {
    for (const role of availableRoles) {
        roleModuleState.value[role.value] = {};

        for (const module of props.modules) {
            const row = props.clinic.roleModuleConfig?.find(
                (config) => config.role === role.value && config.module_key === module.key,
            );

            roleModuleState.value[role.value][module.key] = row ? row.is_enabled : true;
        }
    }
}

onMounted(() => initRoleModuleState());

function isRoleModuleEnabled(role: string, moduleKey: string): boolean {
    return roleModuleState.value[role]?.[moduleKey] ?? true;
}

function toggleRoleModule(role: string, moduleKey: string, enabled: boolean) {
    if (!roleModuleState.value[role]) {
        roleModuleState.value[role] = {};
    }

    roleModuleState.value[role][moduleKey] = enabled;
}

function saveRoleModules() {
    for (const role of availableRoles) {
        const enabledModules = Object.entries(roleModuleState.value[role.value] ?? {})
            .filter(([, enabled]) => enabled)
            .map(([key]) => key);

        router.put(
            roleModuleRoutes.update({ clinic: props.clinic.id }).url,
            { role: role.value, enabled_modules: enabledModules },
            { preserveScroll: true },
        );
    }
}
</script>

<template>
    <Head :title="props.clinic.commercial_name" />

    <div class="space-y-6">
        <div class="flex items-start justify-between gap-4">
            <div class="flex items-start gap-4">
                <img
                    v-if="props.clinic.logo_url"
                    :src="props.clinic.logo_url"
                    alt="Logo"
                    class="h-16 w-16 rounded-full border border-border object-cover"
                />
                <div
                    v-else
                    class="flex h-16 w-16 items-center justify-center rounded-full border border-border bg-muted"
                >
                    <ImageIcon class="h-6 w-6 text-muted-foreground" />
                </div>
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold text-foreground">{{ props.clinic.commercial_name }}</h1>
                        <ClinicStatusBadge :is-active="props.clinic.is_active" :deleted-at="props.clinic.deleted_at" />
                    </div>
                    <a :href="props.clinic.subdomain_url" target="_blank" class="inline-flex items-center gap-1 text-sm text-primary hover:underline">
                        {{ props.clinic.subdomain_url }}
                        <ExternalLink class="h-3 w-3" />
                    </a>
                </div>
            </div>
            <div class="flex gap-2">
                <Button variant="outline" as-child>
                    <Link :href="clinicRoutes.edit({ clinic: props.clinic.id }).url">Editar</Link>
                </Button>
                <Button :variant="props.clinic.is_active ? 'destructive' : 'default'" @click="toggleActive">
                    {{ props.clinic.is_active ? 'Desactivar' : 'Activar' }}
                </Button>
            </div>
        </div>

        <Tabs default-value="general">
            <TabsList class="grid w-full grid-cols-5">
                <TabsTrigger value="general">General</TabsTrigger>
                <TabsTrigger value="branches">Sucursales</TabsTrigger>
                <TabsTrigger value="modules">Módulos</TabsTrigger>
                <TabsTrigger value="users">Usuarios</TabsTrigger>
                <TabsTrigger value="role-modules">Roles y módulos</TabsTrigger>
            </TabsList>

            <TabsContent value="general">
                <Card>
                    <CardContent class="grid grid-cols-2 gap-4 pt-6 text-sm">
                        <div><p class="text-muted-foreground">Razón social</p><p class="font-medium">{{ props.clinic.legal_name }}</p></div>
                        <div><p class="text-muted-foreground">Email de contacto</p><p class="font-medium">{{ props.clinic.contact_email }}</p></div>
                        <div><p class="text-muted-foreground">Teléfono</p><p class="font-medium">{{ props.clinic.contact_phone }}</p></div>
                        <div><p class="text-muted-foreground">Médico responsable</p><p class="font-medium">{{ props.clinic.responsible_vet_name }}</p></div>
                        <div><p class="text-muted-foreground">Cédula profesional</p><p class="font-medium">{{ props.clinic.responsible_vet_license }}</p></div>
                    </CardContent>
                </Card>
            </TabsContent>

            <TabsContent value="branches">
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <CardTitle>Sucursales</CardTitle>
                            <Button size="sm" @click="showBranchForm = !showBranchForm">
                                <Plus class="h-4 w-4" />
                                Nueva sucursal
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div v-if="showBranchForm" class="space-y-3 rounded-lg border border-primary/30 bg-primary/5 p-4">
                            <div class="space-y-2">
                                <Label>Nombre <span class="text-destructive">*</span></Label>
                                <Input v-model="branchForm.name" placeholder="Sucursal Norte" />
                                <p v-if="branchForm.errors.name" class="text-xs text-destructive">{{ branchForm.errors.name }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label>Dirección <span class="text-destructive">*</span></Label>
                                <Input v-model="branchForm.address" placeholder="Av. Insurgentes 100, CDMX" />
                                <p v-if="branchForm.errors.address" class="text-xs text-destructive">{{ branchForm.errors.address }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label>Teléfono</Label>
                                <Input v-model="branchForm.phone" type="tel" placeholder="+52 55 1234 5678" />
                            </div>
                            <div class="flex gap-2">
                                <Button size="sm" :disabled="branchForm.processing" @click="storeBranch">
                                    <Plus class="h-3.5 w-3.5" />
                                    Guardar
                                </Button>
                                <Button size="sm" variant="ghost" @click="showBranchForm = false">Cancelar</Button>
                            </div>
                        </div>

                        <BranchListItem
                            v-for="branch in props.clinic.branches"
                            :key="branch.id"
                            :clinic-id="props.clinic.id"
                            :branch="branch"
                        />
                    </CardContent>
                </Card>
            </TabsContent>

            <TabsContent value="modules">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2 2xl:grid-cols-4">
                    <ModuleToggleCard
                        v-for="module in props.modules"
                        :key="module.key"
                        :clinic-id="props.clinic.id"
                        :module="module"
                    />
                </div>
            </TabsContent>

            <TabsContent value="users">
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <CardTitle>Usuarios</CardTitle>
                            <Button size="sm" @click="showInviteForm = !showInviteForm">
                                <UserPlus class="h-4 w-4" />
                                Invitar usuario
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div v-if="showInviteForm" class="space-y-3 rounded-lg border border-primary/30 bg-primary/5 p-4">
                            <p class="text-sm font-medium text-foreground">Nuevo usuario</p>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="space-y-2">
                                    <Label>Nombre <span class="text-destructive">*</span></Label>
                                    <Input v-model="inviteForm.name" placeholder="María García" />
                                    <p v-if="inviteForm.errors.name" class="text-xs text-destructive">{{ inviteForm.errors.name }}</p>
                                </div>
                                <div class="space-y-2">
                                    <Label>Email <span class="text-destructive">*</span></Label>
                                    <Input v-model="inviteForm.email" type="email" placeholder="usuario@clinica.com" />
                                    <p v-if="inviteForm.errors.email" class="text-xs text-destructive">{{ inviteForm.errors.email }}</p>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <Label>Teléfono</Label>
                                <Input v-model="inviteForm.phone" type="tel" placeholder="+52 55 1234 5678" />
                            </div>
                            <div class="flex gap-2">
                                <Button size="sm" :disabled="inviteForm.processing" @click="inviteUser" v-ripple>
                                    <UserPlus class="h-3.5 w-3.5" />
                                    Enviar invitación
                                </Button>
                                <Button size="sm" variant="ghost" @click="showInviteForm = false">Cancelar</Button>
                            </div>
                        </div>

                        <div v-if="props.clinic.users.length === 0 && !showInviteForm" class="py-8 text-center text-muted-foreground">
                            Sin usuarios registrados en esta clínica.
                        </div>
                        <div v-else class="divide-y divide-border">
                            <div v-for="user in props.clinic.users" :key="user.id" class="py-3">
                                <div v-if="editingUserId !== user.id" class="flex items-center justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <p class="font-medium text-foreground">{{ user.name }}</p>
                                        <p class="text-sm text-muted-foreground">{{ user.email }}</p>
                                        <div class="mt-1 flex flex-wrap gap-1">
                                            <Chip v-for="role in user.roles ?? []" :key="role.name" class="!px-2 !py-0.5 !text-xs">
                                                <template #default>
                                                    <component :is="roleIcons[role.name] ?? BadgeCheck" class="h-3 w-3 shrink-0" />
                                                    <span class="ml-1 text-xs">{{ roleLabel(role.name) }}</span>
                                                </template>
                                            </Chip>
                                        </div>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-2">
                                        <Badge v-if="user.email_verified_at" variant="outline" class="gap-1 text-xs text-success">
                                            <ShieldCheck class="h-3 w-3" />
                                            Verificado
                                        </Badge>
                                        <Badge v-else variant="outline" class="gap-1 text-xs text-warning">
                                            <AlertCircle class="h-3 w-3" />
                                            Sin verificar
                                        </Badge>
                                        <Badge :variant="user.is_active ? 'default' : 'secondary'" class="text-xs">
                                            {{ user.is_active ? 'Activo' : 'Suspendido' }}
                                        </Badge>
                                        <Button size="sm" variant="ghost" @click="editingUserId = user.id" title="Editar">
                                            <Pencil class="h-3.5 w-3.5" />
                                        </Button>
                                        <Button
                                            v-if="isSuperAdmin && !user.email_verified_at"
                                            size="sm"
                                            variant="outline"
                                            class="text-xs"
                                            @click="verifyEmail(user.id)"
                                            v-ripple
                                        >
                                            <ShieldCheck class="h-3.5 w-3.5" />
                                            Verificar
                                        </Button>
                                        <Button
                                            v-if="user.is_active"
                                            size="sm"
                                            variant="outline"
                                            class="text-xs text-warning"
                                            @click="suspendUser(user.id)"
                                            v-ripple
                                        >
                                            <Power class="h-3.5 w-3.5" />
                                            Suspender
                                        </Button>
                                        <Button
                                            v-else
                                            size="sm"
                                            variant="outline"
                                            class="text-xs text-success"
                                            @click="activateUser(user.id)"
                                            v-ripple
                                        >
                                            <RotateCcw class="h-3.5 w-3.5" />
                                            Activar
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            class="text-destructive hover:text-destructive"
                                            @click="deleteUser(user.id, user.name)"
                                            v-ripple
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                        </Button>
                                    </div>
                                </div>

                                <div v-else class="space-y-3 rounded-lg border border-primary/30 bg-primary/5 p-3">
                                    <div class="grid grid-cols-3 gap-3">
                                        <div>
                                            <Label class="text-xs">Nombre</Label>
                                            <Input v-model="editForms[user.id].name" class="mt-1 h-8 text-sm" />
                                        </div>
                                        <div>
                                            <Label class="text-xs">Email</Label>
                                            <Input v-model="editForms[user.id].email" type="email" class="mt-1 h-8 text-sm" />
                                        </div>
                                        <div>
                                            <Label class="text-xs">Teléfono</Label>
                                            <Input v-model="editForms[user.id].phone" class="mt-1 h-8 text-sm" />
                                        </div>
                                    </div>
                                    <div class="flex gap-2">
                                        <Button size="sm" @click="saveUser(user.id)" v-ripple>
                                            <Save class="h-3.5 w-3.5" />
                                            Guardar
                                        </Button>
                                        <Button size="sm" variant="ghost" @click="editingUserId = null">Cancelar</Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </TabsContent>

            <TabsContent value="role-modules">
                <Card>
                    <CardHeader>
                        <CardTitle>Acceso por rol</CardTitle>
                        <p class="text-sm text-muted-foreground">
                            Configura qué módulos puede ver cada rol en esta clínica.
                            Los cambios aplican al acceso del rol para todos sus usuarios.
                        </p>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div v-for="role in availableRoles" :key="role.value" class="space-y-3">
                            <h3 class="flex items-center gap-2 text-sm font-semibold text-foreground">
                                <component :is="roleIcons[role.value] ?? BadgeCheck" class="h-4 w-4 text-muted-foreground" />
                                {{ role.label }}
                            </h3>
                            <div class="grid grid-cols-2 gap-2 md:grid-cols-3 2xl:grid-cols-4">
                                <label
                                    v-for="module in props.modules"
                                    :key="`${role.value}-${module.key}`"
                                    class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition-colors hover:border-primary/50"
                                    :class="isRoleModuleEnabled(role.value, module.key) ? 'border-primary bg-primary/5' : 'border-border'"
                                >
                                    <Checkbox
                                        :model-value="isRoleModuleEnabled(role.value, module.key)"
                                        binary
                                        @update:model-value="toggleRoleModule(role.value, module.key, Boolean($event))"
                                    />
                                    <div class="min-w-0">
                                        <p class="text-xs font-medium text-foreground">{{ module.label }}</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <Button v-ripple @click="saveRoleModules">
                            <Save class="h-4 w-4" />
                            Guardar configuración
                        </Button>
                    </CardContent>
                </Card>
            </TabsContent>
        </Tabs>
    </div>
</template>
