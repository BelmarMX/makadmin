<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Mail, MapPin, Pencil, Phone, Plus, Power, RotateCcw } from 'lucide-vue-next';
import { toast } from '@/lib/toast';
import PatientCard from '@/components/domain/Patient/PatientCard.vue';
import PatientStatusBadge from '@/components/domain/Patient/PatientStatusBadge.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout.vue';
import * as clientRoutes from '@/actions/App/Http/Controllers/Clinic/ClientController';
import * as patientRoutes from '@/actions/App/Http/Controllers/Clinic/PatientController';
import { clinicSlug } from '@/composables/useClinicSlug';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    client: {
        id: number;
        name: string;
        email?: string | null;
        phone?: string | null;
        phone_alt?: string | null;
        avatar?: string | null;
        initials?: string;
        address?: string | null;
        colonia?: string | null;
        city?: string | null;
        state?: string | null;
        postal_code?: string | null;
        curp?: string | null;
        rfc?: string | null;
        notes?: string | null;
        is_active: boolean;
        patients: Array<{
            id: number;
            name: string;
            photo_url?: string | null;
            sex?: string | null;
            age?: string | null;
            microchip?: string | null;
            is_active: boolean;
            is_deceased?: boolean;
            breed?: { id: number; name: string } | null;
            species?: { id: number; name: string } | null;
            coat_color?: { id: number; name: string } | null;
            client?: { id: number; name: string } | null;
        }>;
    };
}>();

const clinic = clinicSlug();

function deactivate(): void {
    router.post(clientRoutes.deactivate({ clinic, client: props.client.id }).url, {}, {
        preserveScroll: true,
        onSuccess: () => toast.success('Tutor desactivado'),
        onError: () => toast.error('Error al desactivar tutor'),
    });
}

function restore(): void {
    router.post(clientRoutes.restore({ clinic, client: props.client.id }).url, {}, {
        preserveScroll: true,
        onSuccess: () => toast.success('Tutor reactivado'),
        onError: () => toast.error('Error al reactivar tutor'),
    });
}
</script>

<template>
    <Head :title="client.name" />

    <div class="space-y-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <div class="mb-2">
                    <PatientStatusBadge :is-active="client.is_active" />
                </div>
                <div class="flex items-center gap-4">
                    <Avatar class="h-16 w-16">
                        <AvatarImage v-if="client.avatar" :src="client.avatar" :alt="client.name" />
                        <AvatarFallback>{{ client.initials ?? 'TT' }}</AvatarFallback>
                    </Avatar>
                    <div>
                        <h1 class="text-2xl font-bold text-foreground">{{ client.name }}</h1>
                        <p class="text-sm text-muted-foreground">Detalle del tutor y mascotas asignadas.</p>
                    </div>
                </div>
            </div>
            <div class="flex gap-2">
                <Button variant="outline" as-child v-tooltip.bottom="'Volver a tutores'">
                    <Link :href="clientRoutes.index(clinic).url">
                        <ArrowLeft class="h-4 w-4" />
                    </Link>
                </Button>
                <Button variant="outline" as-child v-tooltip.bottom="'Editar tutor'">
                    <Link :href="clientRoutes.edit({ clinic, client: client.id }).url">
                        <Pencil class="h-4 w-4" />
                    </Link>
                </Button>
                <Button
                    v-if="client.is_active"
                    variant="destructive"
                    v-ripple
                    v-tooltip.bottom="'Desactivar tutor'"
                    @click="deactivate"
                >
                    <Power class="h-4 w-4" />
                </Button>
                <Button
                    v-else
                    v-ripple
                    v-tooltip.bottom="'Reactivar tutor'"
                    @click="restore"
                >
                    <RotateCcw class="h-4 w-4" />
                </Button>
            </div>
        </div>

        <Tabs default-value="details" class="space-y-4">
            <TabsList>
                <TabsTrigger value="details">Datos del tutor</TabsTrigger>
                <TabsTrigger value="patients">Mascotas</TabsTrigger>
            </TabsList>

            <TabsContent value="details">
                <Card>
                    <CardHeader>
                        <CardTitle>Información general</CardTitle>
                    </CardHeader>
                    <CardContent class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <div v-if="client.email" class="flex items-center gap-2 text-sm text-muted-foreground">
                            <Mail class="h-4 w-4 shrink-0" />
                            <span>{{ client.email }}</span>
                        </div>
                        <div v-if="client.phone" class="flex items-center gap-2 text-sm text-muted-foreground">
                            <Phone class="h-4 w-4 shrink-0" />
                            <span>{{ client.phone }}</span>
                        </div>
                        <div v-if="client.phone_alt" class="flex items-center gap-2 text-sm text-muted-foreground">
                            <Phone class="h-4 w-4 shrink-0" />
                            <span>{{ client.phone_alt }}</span>
                        </div>
                        <div
                            v-if="client.address || client.colonia || client.city || client.state || client.postal_code"
                            class="flex items-start gap-2 text-sm text-muted-foreground md:col-span-2 xl:col-span-3"
                        >
                            <MapPin class="mt-0.5 h-4 w-4 shrink-0" />
                            <span>
                                {{ [client.address, client.colonia, client.city, client.state, client.postal_code].filter(Boolean).join(', ') }}
                            </span>
                        </div>
                        <div v-if="client.curp" class="text-sm text-muted-foreground">
                            <span class="font-medium text-foreground">CURP:</span> {{ client.curp }}
                        </div>
                        <div v-if="client.rfc" class="text-sm text-muted-foreground">
                            <span class="font-medium text-foreground">RFC:</span> {{ client.rfc }}
                        </div>
                        <div v-if="client.notes" class="text-sm text-muted-foreground md:col-span-2 xl:col-span-3">
                            <span class="font-medium text-foreground">Notas:</span> {{ client.notes }}
                        </div>
                    </CardContent>
                </Card>
            </TabsContent>

            <TabsContent value="patients" class="space-y-4">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-foreground">Mascotas</h2>
                        <p class="text-sm text-muted-foreground">Pacientes asignados a este tutor.</p>
                    </div>
                    <Button as-child v-ripple>
                        <Link :href="patientRoutes.create({ clinic, client: client.id }).url">
                            <Plus class="h-4 w-4" />
                            Agregar mascota
                        </Link>
                    </Button>
                </div>

                <div
                    v-if="client.patients.length === 0"
                    class="rounded-lg border border-dashed border-border p-10 text-center text-sm text-muted-foreground"
                >
                    Este tutor aún no tiene mascotas registradas.
                </div>

                <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2 2xl:grid-cols-4">
                    <PatientCard
                        v-for="patient in client.patients"
                        :key="patient.id"
                        :patient="patient"
                        :show-client="false"
                    />
                </div>
            </TabsContent>
        </Tabs>
    </div>
</template>
