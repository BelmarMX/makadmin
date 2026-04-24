<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Building2 } from 'lucide-vue-next';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Card, CardContent } from '@/components/ui/card';
import * as clinicRoutes from '@/actions/App/Http/Controllers/Admin/ClinicController';

defineOptions({ layout: AdminLayout });
</script>

<template>
    <Head title="Dashboard" />

    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-foreground">Panel de Administración</h1>
            <p class="text-sm text-muted-foreground">Bienvenido al panel de superadministrador.</p>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <Link :href="clinicRoutes.index()">
                <Card class="cursor-pointer hover:border-primary/50 transition-colors">
                    <CardContent class="flex items-center gap-4 p-6">
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-primary/10">
                            <Building2 class="h-6 w-6 text-primary" />
                        </div>
                        <div>
                            <p class="font-semibold text-foreground">Clínicas</p>
                            <p class="text-sm text-muted-foreground">Gestionar clínicas</p>
                        </div>
                    </CardContent>
                </Card>
            </Link>
        </div>
    </div>
</template>
