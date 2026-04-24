<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { ExternalLink, Plus } from 'lucide-vue-next';
import AdminLayout from '@/layouts/AdminLayout.vue';
import ClinicStatusBadge from '@/components/domain/Clinic/ClinicStatusBadge.vue';
import ModuleToggleCard from '@/components/domain/Clinic/ModuleToggleCard.vue';
import BranchListItem from '@/components/domain/Clinic/BranchListItem.vue';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import * as clinicRoutes from '@/actions/App/Http/Controllers/Admin/ClinicController';
import * as branchRoutes from '@/actions/App/Http/Controllers/Admin/ClinicBranchController';

defineOptions({ layout: AdminLayout });

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
        deleted_at?: string | null;
        subdomain_url: string;
        branches: Array<{ id: number; name: string; address: string; phone?: string | null; is_main: boolean; is_active: boolean }>;
        users: Array<{ id: number; name: string; email: string }>;
    };
    modules: Array<{ key: string; label: string; description: string; icon: string; dependsOn: string[]; active: boolean }>;
}>();

// Branch creation form
const showBranchForm = ref(false);
const branchForm = useForm({ name: '', address: '', phone: '' });

function storeBranch() {
    branchForm.post(branchRoutes.store({ clinic: props.clinic.id }).url, {
        onSuccess: () => { showBranchForm.value = false; branchForm.reset(); },
        preserveScroll: true,
    });
}

function toggleActive() {
    const url = props.clinic.is_active
        ? clinicRoutes.deactivate({ clinic: props.clinic.id }).url
        : clinicRoutes.activate({ clinic: props.clinic.id }).url;
    router.post(url, {}, { preserveScroll: true });
}
</script>

<template>
    <Head :title="props.clinic.commercial_name" />

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-start justify-between gap-4">
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
            <div class="flex gap-2">
                <Button variant="outline" as-child>
                    <Link :href="clinicRoutes.edit({ clinic: props.clinic.id })">Editar</Link>
                </Button>
                <Button
                    :variant="props.clinic.is_active ? 'destructive' : 'default'"
                    @click="toggleActive"
                >
                    {{ props.clinic.is_active ? 'Desactivar' : 'Activar' }}
                </Button>
            </div>
        </div>

        <!-- Tabs -->
        <Tabs default-value="general">
            <TabsList class="grid w-full grid-cols-4">
                <TabsTrigger value="general">General</TabsTrigger>
                <TabsTrigger value="branches">Sucursales</TabsTrigger>
                <TabsTrigger value="modules">Módulos</TabsTrigger>
                <TabsTrigger value="users">Usuarios</TabsTrigger>
            </TabsList>

            <!-- General -->
            <TabsContent value="general">
                <Card>
                    <CardContent class="grid grid-cols-2 gap-4 pt-6 text-sm">
                        <div><p class="text-muted-foreground">Razón social</p><p class="font-medium">{{ props.clinic.legal_name }}</p></div>
                        <div><p class="text-muted-foreground">Email</p><p class="font-medium">{{ props.clinic.contact_email }}</p></div>
                        <div><p class="text-muted-foreground">Teléfono</p><p class="font-medium">{{ props.clinic.contact_phone }}</p></div>
                        <div><p class="text-muted-foreground">Médico responsable</p><p class="font-medium">{{ props.clinic.responsible_vet_name }}</p></div>
                        <div><p class="text-muted-foreground">Cédula</p><p class="font-medium">{{ props.clinic.responsible_vet_license }}</p></div>
                    </CardContent>
                </Card>
            </TabsContent>

            <!-- Branches -->
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
                                <Label>Nombre</Label>
                                <Input v-model="branchForm.name" placeholder="Sucursal Norte" />
                                <p v-if="branchForm.errors.name" class="text-xs text-destructive">{{ branchForm.errors.name }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label>Dirección</Label>
                                <Input v-model="branchForm.address" placeholder="Av. Insurgentes 100, CDMX" />
                                <p v-if="branchForm.errors.address" class="text-xs text-destructive">{{ branchForm.errors.address }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label>Teléfono</Label>
                                <Input v-model="branchForm.phone" type="tel" />
                            </div>
                            <div class="flex gap-2">
                                <Button size="sm" :disabled="branchForm.processing" @click="storeBranch">Guardar</Button>
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

            <!-- Modules -->
            <TabsContent value="modules">
                <div class="space-y-3">
                    <ModuleToggleCard
                        v-for="m in props.modules"
                        :key="m.key"
                        :clinic-id="props.clinic.id"
                        :module="m"
                    />
                </div>
            </TabsContent>

            <!-- Users -->
            <TabsContent value="users">
                <Card>
                    <CardContent class="pt-6">
                        <div v-if="props.clinic.users.length === 0" class="py-8 text-center text-muted-foreground">
                            Sin usuarios registrados en esta clínica.
                        </div>
                        <div v-else class="divide-y divide-border">
                            <div v-for="user in props.clinic.users" :key="user.id" class="flex items-center justify-between py-3">
                                <div>
                                    <p class="font-medium text-foreground">{{ user.name }}</p>
                                    <p class="text-sm text-muted-foreground">{{ user.email }}</p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </TabsContent>
        </Tabs>
    </div>
</template>
