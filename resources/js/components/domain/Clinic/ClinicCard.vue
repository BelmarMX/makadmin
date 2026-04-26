<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Building2, ExternalLink, Mail, Phone } from 'lucide-vue-next';
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
    <Card class="transition-colors hover:border-primary/50">
        <CardHeader class="pb-3">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-3">
                    <img
                        v-if="props.clinic.logo_url"
                        :src="props.clinic.logo_url"
                        alt="Logo"
                        class="h-10 w-10 rounded-full border border-border object-cover"
                    />
                    <div
                        v-else
                        class="flex h-10 w-10 items-center justify-center rounded-full border border-border bg-muted"
                    >
                        <Building2 class="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <Link
                            :href="
                                adminRoutes.show({ clinic: props.clinic.id })
                            "
                            class="font-semibold text-foreground transition-colors hover:text-primary"
                        >
                            {{ props.clinic.commercial_name }}
                        </Link>
                    </div>
                </div>
                <ClinicStatusBadge
                    :is-active="props.clinic.is_active"
                    :deleted-at="props.clinic.deleted_at"
                />
            </div>
        </CardHeader>
        <CardContent class="space-y-2 text-sm text-muted-foreground">
            <a
                :href="props.clinic.subdomain_url"
                target="_blank"
                class="flex items-center gap-2 py-0.5 text-primary/80 hover:text-primary"
            >
                <ExternalLink class="h-3.5 w-3.5 shrink-0" />
                {{ props.clinic.subdomain_url }}
            </a>
            <p class="flex items-center gap-2">
                <Mail class="h-3.5 w-3.5 shrink-0" />
                {{ props.clinic.contact_email }}
            </p>
            <p class="flex items-center gap-2">
                <Phone class="h-3.5 w-3.5 shrink-0" />
                {{ props.clinic.contact_phone }}
            </p>
        </CardContent>
    </Card>
</template>
