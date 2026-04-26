<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Calendar, Dna, Pencil, Power, RotateCcw, Scale, UserRound } from 'lucide-vue-next';
import { toast } from '@/lib/toast';
import PatientStatusBadge from '@/components/domain/Patient/PatientStatusBadge.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import * as clientRoutes from '@/actions/App/Http/Controllers/Clinic/ClientController';
import * as patientRoutes from '@/actions/App/Http/Controllers/Clinic/PatientController';
import { clinicSlug } from '@/composables/useClinicSlug';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    patient: {
        id: number;
        name: string;
        age?: string | null;
        photo_url?: string | null;
        birth_date?: string | null;
        microchip?: string | null;
        notes?: string | null;
        size?: { value?: string } | string | null;
        weight_kg?: string | number | null;
        is_active: boolean;
        is_sterilized: boolean;
        is_deceased: boolean;
        deceased_at?: string | null;
        client?: { id: number; name: string; email?: string | null; phone?: string | null } | null;
        species?: { id: number; name: string } | null;
        breed?: { id: number; name: string } | null;
        temperament?: { id: number; name: string } | null;
        coat_color?: { id: number; name: string } | null;
    };
}>();

const clinic = clinicSlug();

function initials(name: string): string {
    return name
        .split(' ')
        .filter(Boolean)
        .map((word) => word[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();
}

function deactivate(): void {
    router.post(patientRoutes.deactivate({ clinic, patient: props.patient.id }).url, {}, {
        preserveScroll: true,
        onSuccess: () => toast.success('Paciente desactivado'),
        onError: () => toast.error('Error al desactivar paciente'),
    });
}

function restore(): void {
    router.post(patientRoutes.restore({ clinic, patient: props.patient.id }).url, {}, {
        preserveScroll: true,
        onSuccess: () => toast.success('Paciente reactivado'),
        onError: () => toast.error('Error al reactivar paciente'),
    });
}
</script>

<template>
    <Head :title="patient.name" />

    <div class="space-y-6">
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <Avatar class="h-16 w-16">
                    <AvatarImage v-if="patient.photo_url" :src="patient.photo_url" :alt="patient.name" />
                    <AvatarFallback>{{ initials(patient.name) }}</AvatarFallback>
                </Avatar>
                <div>
                    <div class="mb-2">
                        <PatientStatusBadge :is-active="patient.is_active" :is-deceased="patient.is_deceased" />
                    </div>
                    <h1 class="text-2xl font-bold text-foreground">{{ patient.name }}</h1>
                    <p class="text-sm text-muted-foreground">{{ patient.species?.name ?? 'Sin especie' }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                <Button variant="outline" as-child v-tooltip.bottom="'Volver al tutor'">
                    <Link :href="clientRoutes.show({ clinic, client: patient.client?.id ?? 0 }).url">
                        <ArrowLeft class="h-4 w-4" />
                    </Link>
                </Button>
                <Button variant="outline" as-child v-tooltip.bottom="'Editar paciente'">
                    <Link :href="patientRoutes.edit({ clinic, patient: patient.id }).url">
                        <Pencil class="h-4 w-4" />
                    </Link>
                </Button>
                <Button
                    v-if="patient.is_active"
                    variant="destructive"
                    v-ripple
                    v-tooltip.bottom="'Desactivar paciente'"
                    @click="deactivate"
                >
                    <Power class="h-4 w-4" />
                </Button>
                <Button
                    v-else
                    v-ripple
                    v-tooltip.bottom="'Reactivar paciente'"
                    @click="restore"
                >
                    <RotateCcw class="h-4 w-4" />
                </Button>
            </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-[1.2fr_0.8fr]">
            <Card>
                <CardHeader><CardTitle>Ficha del paciente</CardTitle></CardHeader>
                <CardContent class="grid gap-4 md:grid-cols-2">
                    <div class="flex items-center gap-2 text-sm text-muted-foreground">
                        <Dna class="h-4 w-4 shrink-0" />
                        <span>{{ patient.breed?.name ?? 'Sin raza definida' }}</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-muted-foreground">
                        <Calendar class="h-4 w-4 shrink-0" />
                        <span>{{ patient.age ?? patient.birth_date ?? 'Sin edad registrada' }}</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-muted-foreground">
                        <Scale class="h-4 w-4 shrink-0" />
                        <span>{{ patient.weight_kg ? `${patient.weight_kg} kg` : 'Peso no registrado' }}</span>
                    </div>
                    <div class="text-sm text-muted-foreground">
                        <span class="font-medium text-foreground">Microchip:</span>
                        {{ patient.microchip || 'No registrado' }}
                    </div>
                    <div class="text-sm text-muted-foreground">
                        <span class="font-medium text-foreground">Temperamento:</span>
                        {{ patient.temperament?.name || 'No definido' }}
                    </div>
                    <div class="text-sm text-muted-foreground">
                        <span class="font-medium text-foreground">Color:</span>
                        {{ patient.coat_color?.name || 'No definido' }}
                    </div>
                    <div class="text-sm text-muted-foreground">
                        <span class="font-medium text-foreground">Esterilizado:</span>
                        {{ patient.is_sterilized ? 'Sí' : 'No' }}
                    </div>
                    <div
                        v-if="patient.is_deceased && patient.deceased_at"
                        class="text-sm text-muted-foreground"
                    >
                        <span class="font-medium text-foreground">Fallecimiento:</span>
                        {{ patient.deceased_at }}
                    </div>
                    <div v-if="patient.notes" class="text-sm text-muted-foreground md:col-span-2">
                        <span class="font-medium text-foreground">Notas:</span> {{ patient.notes }}
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader><CardTitle>Tutor principal</CardTitle></CardHeader>
                <CardContent class="space-y-3 text-sm">
                    <div class="flex items-center gap-2 text-foreground">
                        <UserRound class="h-4 w-4 shrink-0 text-muted-foreground" />
                        <Link
                            v-if="patient.client"
                            :href="clientRoutes.show({ clinic, client: patient.client.id }).url"
                            class="font-medium hover:underline"
                        >
                            {{ patient.client.name }}
                        </Link>
                    </div>
                    <p v-if="patient.client?.email" class="text-muted-foreground">
                        {{ patient.client.email }}
                    </p>
                    <p v-if="patient.client?.phone" class="text-muted-foreground">
                        {{ patient.client.phone }}
                    </p>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
