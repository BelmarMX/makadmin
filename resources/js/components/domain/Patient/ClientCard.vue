<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Mail, PawPrint, Pencil, Phone } from 'lucide-vue-next';
import PatientStatusBadge from '@/components/domain/Patient/PatientStatusBadge.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import * as clientRoutes from '@/actions/App/Http/Controllers/Clinic/ClientController';
import { clinicSlug } from '@/composables/useClinicSlug';

defineProps<{
    client: {
        id: number;
        name: string;
        email?: string | null;
        phone?: string | null;
        avatar?: string | null;
        initials?: string;
        is_active: boolean;
        patients_count?: number;
    };
}>();

const clinic = clinicSlug();
</script>

<template>
    <Card>
        <CardContent class="flex items-start gap-4 p-4">
            <Avatar class="h-12 w-12 shrink-0">
                <AvatarImage v-if="client.avatar" :src="client.avatar" :alt="client.name" />
                <AvatarFallback>{{ client.initials ?? 'TT' }}</AvatarFallback>
            </Avatar>

            <div class="min-w-0 flex-1 space-y-2">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <Link
                            :href="clientRoutes.show({ clinic, client: client.id }).url"
                            class="font-medium text-foreground hover:underline"
                        >
                            {{ client.name }}
                        </Link>
                        <div
                            v-if="client.email"
                            class="mt-1 flex items-center gap-2 text-sm text-muted-foreground"
                        >
                            <Mail class="h-3.5 w-3.5 shrink-0" />
                            <span class="truncate">{{ client.email }}</span>
                        </div>
                        <div
                            v-if="client.phone"
                            class="flex items-center gap-2 text-sm text-muted-foreground"
                        >
                            <Phone class="h-3.5 w-3.5 shrink-0" />
                            <span>{{ client.phone }}</span>
                        </div>
                    </div>
                    <PatientStatusBadge :is-active="client.is_active" />
                </div>

                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2 text-sm text-muted-foreground">
                        <PawPrint class="h-4 w-4 shrink-0" />
                        <span>{{ client.patients_count ?? 0 }} mascotas activas</span>
                    </div>

                    <Button
                        variant="ghost"
                        size="icon"
                        as-child
                        v-tooltip.left="'Editar tutor'"
                    >
                        <Link :href="clientRoutes.edit({ clinic, client: client.id }).url">
                            <Pencil class="h-4 w-4" />
                        </Link>
                    </Button>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
