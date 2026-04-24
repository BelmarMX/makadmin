<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus, Building2 } from 'lucide-vue-next';
import AdminLayout from '@/layouts/AdminLayout.vue';
import ClinicCard from '@/components/domain/Clinic/ClinicCard.vue';
import { Button } from '@/components/ui/button';
import * as clinicRoutes from '@/actions/App/Http/Controllers/Admin/ClinicController';

defineOptions({ layout: AdminLayout });

defineProps<{
    clinics: {
        data: Array<{
            id: number;
            slug: string;
            commercial_name: string;
            contact_email: string;
            contact_phone: string;
            is_active: boolean;
            deleted_at?: string | null;
            subdomain_url?: string;
        }>;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
}>();
</script>

<template>
    <Head title="Clínicas" />

    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Clínicas</h1>
                <p class="text-sm text-muted-foreground">Gestión de todas las clínicas en el sistema.</p>
            </div>
            <Button as-child>
                <Link :href="clinicRoutes.create()">
                    <Plus class="h-4 w-4" />
                    Nueva clínica
                </Link>
            </Button>
        </div>

        <div v-if="clinics.data.length === 0" class="flex flex-col items-center justify-center rounded-lg border border-dashed border-border py-16 text-center">
            <Building2 class="mb-4 h-12 w-12 text-muted-foreground" />
            <p class="font-medium text-foreground">Sin clínicas registradas</p>
            <p class="text-sm text-muted-foreground">Crea la primera clínica para comenzar.</p>
        </div>

        <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            <ClinicCard v-for="clinic in clinics.data" :key="clinic.id" :clinic="clinic" />
        </div>

        <div v-if="clinics.links.length > 3" class="flex justify-center gap-1">
            <template v-for="link in clinics.links" :key="link.label">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    :class="['rounded border px-3 py-1 text-sm transition-colors', link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted']"
                    v-html="link.label"
                />
                <span v-else class="cursor-default rounded border border-transparent px-3 py-1 text-sm text-muted-foreground" v-html="link.label" />
            </template>
        </div>
    </div>
</template>
