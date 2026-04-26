<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Palette, Pencil } from 'lucide-vue-next';
import PatientStatusBadge from '@/components/domain/Patient/PatientStatusBadge.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import * as patientRoutes from '@/actions/App/Http/Controllers/Clinic/PatientController';
import { clinicSlug } from '@/composables/useClinicSlug';

defineProps<{
    showClient?: boolean;
    patient: {
        id: number;
        name: string;
        photo_url?: string | null;
        sex?: string | null;
        age?: string | null;
        breed?: { id: number; name: string } | null;
        species?: { id: number; name: string } | null;
        coat_color?: { id: number; name: string } | null;
        client?: { id: number; name: string } | null;
        microchip?: string | null;
        is_active: boolean;
        is_deceased?: boolean;
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

function sexTooltip(sex?: string | null): string {
    return sex === 'male' ? 'Macho' : sex === 'female' ? 'Hembra' : 'Indefinido';
}
</script>

<template>
    <Card>
        <CardContent class="flex items-start gap-4 p-4">
            <Avatar class="h-12 w-12 shrink-0">
                <AvatarImage
                    v-if="patient.photo_url"
                    :src="patient.photo_url"
                    :alt="patient.name"
                />
                <AvatarFallback>{{ initials(patient.name) }}</AvatarFallback>
            </Avatar>

            <div class="min-w-0 flex-1 space-y-2">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <Link
                                :href="patientRoutes.show({ clinic, patient: patient.id }).url"
                                class="font-medium text-foreground hover:underline"
                            >
                                {{ patient.name }}
                            </Link>
                            <span
                                v-if="patient.sex === 'male'"
                                class="inline-flex shrink-0 text-sm font-semibold leading-none text-sky-500"
                                v-tooltip.top="sexTooltip(patient.sex)"
                            >
                                ♂
                            </span>
                            <span
                                v-else-if="patient.sex === 'female'"
                                class="inline-flex shrink-0 text-sm font-semibold leading-none text-pink-500"
                                v-tooltip.top="sexTooltip(patient.sex)"
                            >
                                ♀
                            </span>
                            <span
                                v-else
                                class="inline-flex shrink-0 text-sm font-semibold leading-none text-muted-foreground"
                                v-tooltip.top="sexTooltip(patient.sex)"
                            >
                                ?
                            </span>
                        </div>

                        <div class="mt-1 text-sm text-muted-foreground">
                            {{ patient.species?.name ?? 'Sin especie' }}<span v-if="patient.breed?.name"> - {{ patient.breed.name }}</span>
                        </div>
                        <div v-if="patient.coat_color?.name" class="flex items-center gap-2 text-sm text-muted-foreground">
                            <Palette class="h-3.5 w-3.5 shrink-0" />
                            <span>{{ patient.coat_color.name }}</span>
                        </div>
                        <div v-if="patient.age" class="text-sm text-muted-foreground">{{ patient.age }}</div>
                        <div
                            v-if="showClient !== false && patient.client?.name"
                            class="text-sm text-muted-foreground"
                        >
                            {{ patient.client.name }}
                        </div>
                    </div>
                    <PatientStatusBadge
                        :is-active="patient.is_active"
                        :is-deceased="patient.is_deceased ?? false"
                    />
                </div>

                <div class="flex items-center justify-between gap-3">
                    <p class="text-sm text-muted-foreground">
                        {{ patient.microchip ? `Microchip: ${patient.microchip}` : 'Sin microchip registrado' }}
                    </p>

                    <Button
                        variant="ghost"
                        size="icon"
                        as-child
                        v-tooltip.left="'Editar paciente'"
                    >
                        <Link :href="patientRoutes.edit({ clinic, patient: patient.id }).url">
                            <Pencil class="h-4 w-4" />
                        </Link>
                    </Button>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
