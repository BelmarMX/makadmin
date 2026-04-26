<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import CropModal from '@/components/CropModal.vue';
import { ref, computed } from 'vue';
import { toast } from '@/lib/toast';
import {
    ChevronLeft,
    ChevronRight,
    Check,
    Building2,
    FileText,
    MapPin,
    Puzzle,
} from 'lucide-vue-next';
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

const STEPS = [
    { label: 'Identidad', icon: Building2 },
    { label: 'Datos fiscales', icon: FileText },
    { label: 'Sucursal principal', icon: MapPin },
    { label: 'Módulos y Admin', icon: Puzzle },
];

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

const cropOpen = ref(false);
const cropSrc = ref<string | null>(null);

function onLogoSelected(file: File) {
    cropSrc.value = URL.createObjectURL(file);
    cropOpen.value = true;
}

function onCropConfirm(blob: Blob) {
    cropOpen.value = false;
    form.logo = new File([blob], 'logo.webp', { type: 'image/webp' });
}

// Campos requeridos por paso para validación frontend
const stepRequiredFields: Record<number, string[]> = {
    0: ['slug', 'commercial_name', 'legal_name', 'contact_email', 'contact_phone'],
    1: ['responsible_vet_name', 'responsible_vet_license'],
    2: ['main_branch.name', 'main_branch.address'],
    3: ['admin.name', 'admin.email'],
};

function getFieldValue(field: string): string {
    const parts = field.split('.');
    if (parts.length === 1) return ((form as unknown as Record<string, unknown>)[parts[0]] as string) ?? '';
    if (parts[0] === 'main_branch') return form.main_branch[parts[1] as keyof typeof form.main_branch];
    if (parts[0] === 'admin') return form.admin[parts[1] as keyof typeof form.admin];
    return '';
}

const stepErrors = ref<string[]>([]);

function validateStep(step: number): boolean {
    const required = stepRequiredFields[step] ?? [];
    const missing = required.filter((f) => {
        if (f === 'modules') return form.modules.length === 0;
        return !getFieldValue(f).trim();
    });
    stepErrors.value = missing;
    if (missing.length > 0) {
        toast.error('Completa los campos requeridos antes de continuar.');
        return false;
    }
    // Paso 3: validar al menos un módulo
    if (step === 3 && form.modules.length === 0) {
        toast.error('Selecciona al menos un módulo.');
        return false;
    }
    return true;
}

function next() {
    if (currentStep.value < STEPS.length - 1) {
        if (validateStep(currentStep.value)) {
            stepErrors.value = [];
            currentStep.value++;
        }
    }
}

function prev() {
    if (currentStep.value > 0) {
        stepErrors.value = [];
        currentStep.value--;
    }
}

// Mapa de campo de error → índice de paso
const errorStepMap: Record<string, number> = {
    slug: 0, commercial_name: 0, legal_name: 0, contact_email: 0, contact_phone: 0,
    rfc: 1, fiscal_regime: 1, tax_address: 1, responsible_vet_name: 1, responsible_vet_license: 1,
    'main_branch.name': 2, 'main_branch.address': 2, 'main_branch.phone': 2,
    modules: 3, 'admin.name': 3, 'admin.email': 3, 'admin.phone': 3,
};

function submit() {
    form.post(clinicRoutes.store().url, {
        onError: (errors) => {
            // Saltar al primer paso con errores
            const firstErrorKey = Object.keys(errors)[0];
            if (firstErrorKey !== undefined) {
                const targetStep = errorStepMap[firstErrorKey] ?? 0;
                currentStep.value = targetStep;
            }
            const count = Object.keys(errors).length;
            toast.error(`Hay ${count} error${count !== 1 ? 'es' : ''} en el formulario. Revisa los campos marcados.`);
        },
    });
}

const hasStepErrors = computed(() => stepErrors.value.length > 0 || Object.keys(form.errors).length > 0);
</script>

<template>
    <Head title="Nueva clínica" />

    <div class="w-full space-y-4">
        <div>
            <h1 class="text-2xl font-bold text-foreground">Nueva clínica</h1>
            <p class="text-sm text-muted-foreground">Completa los 4 pasos para dar de alta la clínica.</p>
        </div>

        <!-- Botones de navegación ARRIBA -->
        <div class="flex items-center justify-between gap-2">
            <Button variant="outline" :disabled="currentStep === 0" @click="prev">
                <ChevronLeft class="h-4 w-4" />
                Anterior
            </Button>
            <div class="text-sm text-muted-foreground">
                Paso {{ currentStep + 1 }} de {{ STEPS.length }}
            </div>
            <Button v-if="currentStep < STEPS.length - 1" @click="next">
                Siguiente
                <ChevronRight class="h-4 w-4" />
            </Button>
            <Button v-else :disabled="form.processing" @click="submit">
                <Check class="h-4 w-4" />
                Crear clínica
            </Button>
        </div>

        <!-- Step indicators -->
        <div class="flex items-center gap-2">
            <template v-for="(step, idx) in STEPS" :key="idx">
                <div class="flex items-center gap-2">
                    <div
                        :class="[
                            'flex h-7 w-7 items-center justify-center rounded-full text-xs font-semibold transition-colors',
                            idx < currentStep
                                ? 'bg-success text-white'
                                : idx === currentStep
                                  ? 'bg-primary text-primary-foreground'
                                  : 'bg-muted text-muted-foreground',
                        ]"
                    >
                        <Check v-if="idx < currentStep" class="h-3.5 w-3.5" />
                        <component :is="step.icon" v-else class="h-3.5 w-3.5" />
                    </div>
                    <span :class="['hidden text-sm sm:inline', idx === currentStep ? 'font-medium text-foreground' : 'text-muted-foreground']">
                        {{ step.label }}
                    </span>
                </div>
                <div v-if="idx < STEPS.length - 1" class="h-px flex-1 bg-border" />
            </template>
        </div>

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <component :is="STEPS[currentStep].icon" class="h-5 w-5 text-primary" />
                    {{ STEPS[currentStep].label }}
                </CardTitle>
            </CardHeader>
            <CardContent>
                <StepIdentity v-if="currentStep === 0" :form="form" @upload-logo="onLogoSelected" />
                <StepFiscal v-else-if="currentStep === 1" :form="form" :fiscal-regimes="props.fiscalRegimes" />
                <StepBranch v-else-if="currentStep === 2" :form="form" />
                <StepModulesAdmin v-else-if="currentStep === 3" :form="form" :modules="props.modules" />
            </CardContent>
        </Card>

        <CropModal
            :open="cropOpen"
            :image-src="cropSrc"
            @confirm="onCropConfirm"
            @cancel="cropOpen = false"
            @update:open="cropOpen = $event"
        />

        <!-- Botones de navegación ABAJO (duplicados para comodidad en formularios largos) -->
        <div class="flex items-center justify-between gap-2">
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
