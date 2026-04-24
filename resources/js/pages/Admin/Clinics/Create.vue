<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { ChevronLeft, ChevronRight, Check } from 'lucide-vue-next';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import StepIdentity from '@/components/domain/Clinic/ClinicWizard/StepIdentity.vue';
import StepFiscal from '@/components/domain/Clinic/ClinicWizard/StepFiscal.vue';
import StepBranch from '@/components/domain/Clinic/ClinicWizard/StepBranch.vue';
import StepModulesAdmin from '@/components/domain/Clinic/ClinicWizard/StepModulesAdmin.vue';
import * as clinicRoutes from '@/actions/App/Http/Controllers/Admin/ClinicController';

defineOptions({ layout: AdminLayout });

const props = defineProps<{
    modules: Array<{ key: string; label: string; description: string; icon: string; dependsOn: string[] }>;
    fiscalRegimes: Array<{ value: string; label: string }>;
}>();

const STEPS = ['Identidad', 'Datos fiscales', 'Sucursal principal', 'Módulos y Admin'];
const currentStep = ref(0);

const form = useForm({
    slug: '',
    commercial_name: '',
    legal_name: '',
    contact_email: '',
    contact_phone: '',
    primary_color: '#3b82f6',
    rfc: '',
    fiscal_regime: '',
    tax_address: '',
    responsible_vet_name: '',
    responsible_vet_license: '',
    main_branch: { name: '', address: '', phone: '' },
    modules: [] as string[],
    admin: { name: '', email: '', phone: '' },
    logo: null as File | null,
});

function next() {
    if (currentStep.value < STEPS.length - 1) currentStep.value++;
}
function prev() {
    if (currentStep.value > 0) currentStep.value--;
}

function submit() {
    form.post(clinicRoutes.store().url);
}
</script>

<template>
    <Head title="Nueva clínica" />

    <div class="mx-auto max-w-3xl space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-foreground">Nueva clínica</h1>
            <p class="text-sm text-muted-foreground">Completa los 4 pasos para dar de alta la clínica.</p>
        </div>

        <!-- Step indicators -->
        <div class="flex items-center gap-2">
            <template v-for="(step, idx) in STEPS" :key="idx">
                <div class="flex items-center gap-2">
                    <div
                        :class="[
                            'flex h-7 w-7 items-center justify-center rounded-full text-xs font-semibold transition-colors',
                            idx < currentStep ? 'bg-success text-white' : idx === currentStep ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground',
                        ]"
                    >
                        <Check v-if="idx < currentStep" class="h-3.5 w-3.5" />
                        <span v-else>{{ idx + 1 }}</span>
                    </div>
                    <span :class="['text-sm', idx === currentStep ? 'font-medium text-foreground' : 'text-muted-foreground']">{{ step }}</span>
                </div>
                <div v-if="idx < STEPS.length - 1" class="h-px flex-1 bg-border" />
            </template>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>{{ STEPS[currentStep] }}</CardTitle>
            </CardHeader>
            <CardContent>
                <StepIdentity v-if="currentStep === 0" :form="form" />
                <StepFiscal v-else-if="currentStep === 1" :form="form" :fiscal-regimes="props.fiscalRegimes" />
                <StepBranch v-else-if="currentStep === 2" :form="form" />
                <StepModulesAdmin v-else-if="currentStep === 3" :form="form" :modules="props.modules" />
            </CardContent>
        </Card>

        <div class="flex justify-between">
            <Button variant="outline" :disabled="currentStep === 0" @click="prev">
                <ChevronLeft class="h-4 w-4" />
                Anterior
            </Button>
            <Button v-if="currentStep < STEPS.length - 1" @click="next">
                Siguiente
                <ChevronRight class="h-4 w-4" />
            </Button>
            <Button v-else :disabled="form.processing" @click="submit">
                <Check class="h-4 w-4" />
                Crear clínica
            </Button>
        </div>
    </div>
</template>
