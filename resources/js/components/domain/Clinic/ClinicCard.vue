<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Building2, ExternalLink } from 'lucide-vue-next';
import ClinicStatusBadge from './ClinicStatusBadge.vue';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import * as adminRoutes from '@/actions/App/Http/Controllers/Admin/ClinicController';

const props = defineProps<{
    clinic: {
        id: number;
        slug: string;
        commercial_name: string;
        contact_email: string;
        contact_phone: string;
        is_active: boolean;
        logo_url?: string | null;
        deleted_at?: string | null;
        subdomain_url?: string;
        branches?: Array<{ name: string; is_main: boolean }>;
    };
}>();
</script>

<template>
    <Card class="hover:border-primary/50 transition-colors">
        <CardHeader class="pb-3">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-3">
                    <img
                        v-if="props.clinic.logo_url"
                        :src="props.clinic.logo_url"
                        alt="Logo"
                        class="h-10 w-10 rounded-full border border-border object-cover"
                    />
                    <div v-else class="flex h-10 w-10 items-center justify-center rounded-full border border-border bg-muted">
                        <Building2 class="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <Link
                            :href="adminRoutes.show({ clinic: props.clinic.id })"
                            class="font-semibold text-foreground hover:text-primary transition-colors"
                        >
                            {{ props.clinic.commercial_name }}
                        </Link>
                        <p class="text-xs text-muted-foreground font-mono">{{ props.clinic.slug }}</p>
                    </div>
                </div>
                <ClinicStatusBadge :is-active="props.clinic.is_active" :deleted-at="props.clinic.deleted_at" />
            </div>
        </CardHeader>
        <CardContent class="space-y-2 text-sm text-muted-foreground">
            <p>{{ props.clinic.contact_email }}</p>
            <p>{{ props.clinic.contact_phone }}</p>
            <div v-if="props.clinic.subdomain_url" class="pt-1">
                <a
                    :href="props.clinic.subdomain_url"
                    target="_blank"
                    class="inline-flex items-center gap-1 text-xs text-primary hover:underline"
                >
                    {{ props.clinic.subdomain_url }}
                    <ExternalLink class="h-3 w-3" />
                </a>
            </div>
        </CardContent>
    </Card>
</template>
