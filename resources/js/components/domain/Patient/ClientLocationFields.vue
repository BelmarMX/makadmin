<script setup lang="ts">
import { MapPinned } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import FloatLabel from 'primevue/floatlabel';
import CascadeSelect from 'primevue/cascadeselect';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import * as catalogRoutes from '@/routes/api/catalog';
import * as patientCatalogRoutes from '@/actions/App/Http/Controllers/Clinic/Api/PatientCatalogController';
import { clinicSlug } from '@/composables/useClinicSlug';
import InputError from '@/components/InputError.vue';
import InlineCatalogCreateDialog from '@/components/domain/Patient/InlineCatalogCreateDialog.vue';

type PostalCodeResult = {
    id: number;
    code: string;
    settlement: string;
    state: { id: number; name: string };
    municipality: { id: number; name: string };
};

type SimpleOption = { id: number | string; name: string };
type CascadeGroup = { name: string; items: SimpleOption[] };

const props = defineProps<{
    postalCode?: string | null;
    state?: string | null;
    city?: string | null;
    colonia?: string | null;
    errors?: Record<string, string | undefined>;
}>();

const emit = defineEmits<{
    'update:postalCode': [value: string];
    'update:state': [value: string];
    'update:city': [value: string];
    'update:colonia': [value: string];
}>();

const clinic = clinicSlug();
const loading = ref(false);
const results = ref<PostalCodeResult[]>([]);
const selectedState = ref<SimpleOption | null>(null);
const selectedCity = ref<SimpleOption | null>(null);
const selectedColonia = ref<SimpleOption | null>(null);
let debounceTimer: ReturnType<typeof setTimeout>;

const stateOptions = computed<CascadeGroup[]>(() => {
    const unique = new Map<number, SimpleOption>();

    for (const result of results.value) {
        unique.set(result.state.id, { id: result.state.id, name: result.state.name });
    }

    return unique.size > 0 ? [{ name: 'Estados', items: [...unique.values()] }] : [];
});

const cityOptions = computed<CascadeGroup[]>(() => {
    if (!selectedState.value) {
        return [];
    }

    const unique = new Map<number, SimpleOption>();

    for (const result of results.value.filter((item) => item.state.id === selectedState.value?.id)) {
        unique.set(result.municipality.id, { id: result.municipality.id, name: result.municipality.name });
    }

    return unique.size > 0 ? [{ name: selectedState.value.name, items: [...unique.values()] }] : [];
});

const coloniaOptions = computed<CascadeGroup[]>(() => {
    if (!selectedState.value || !selectedCity.value) {
        return [];
    }

    const unique = new Map<string, SimpleOption>();

    for (const result of results.value.filter(
        (item) => item.state.id === selectedState.value?.id && item.municipality.id === selectedCity.value?.id,
    )) {
        unique.set(result.settlement, { id: result.settlement, name: result.settlement });
    }

    return unique.size > 0 ? [{ name: selectedCity.value.name, items: [...unique.values()] }] : [];
});

async function fetchPostalData(postalCode: string): Promise<void> {
    if (postalCode.length !== 5) {
        results.value = [];
        return;
    }

    loading.value = true;

    try {
        const response = await fetch(
            catalogRoutes.postalCodes.url({
                query: { q: postalCode, limit: 100 },
            }),
        );

        if (!response.ok) {
            results.value = [];
            return;
        }

        const json = await response.json();
        results.value = json.data ?? [];
        syncFromProps();
    } finally {
        loading.value = false;
    }
}

function syncFromProps(): void {
    selectedState.value = stateOptions.value[0]?.items.find((item) => item.name === props.state)
        ?? (props.state ? { id: props.state, name: props.state } : null);
    selectedCity.value = cityOptions.value[0]?.items.find((item) => item.name === props.city)
        ?? (props.city ? { id: props.city, name: props.city } : null);
    selectedColonia.value = coloniaOptions.value[0]?.items.find((item) => item.name === props.colonia)
        ?? (props.colonia ? { id: props.colonia, name: props.colonia } : null);

    if (!selectedState.value && stateOptions.value[0]?.items.length === 1) {
        onStateChange(stateOptions.value[0].items[0]);
    }

    if (!selectedCity.value && cityOptions.value[0]?.items.length === 1) {
        onCityChange(cityOptions.value[0].items[0]);
    }
}

function onStateChange(option: SimpleOption | null): void {
    selectedState.value = option;
    emit('update:state', option?.name ?? '');

    if (selectedCity.value && !cityOptions.value[0]?.items.some((item) => item.id === selectedCity.value?.id)) {
        onCityChange(null);
    }
}

function onCityChange(option: SimpleOption | null): void {
    selectedCity.value = option;
    emit('update:city', option?.name ?? '');

    if (selectedColonia.value && !coloniaOptions.value[0]?.items.some((item) => item.id === selectedColonia.value?.id)) {
        onColoniaChange(null);
    }
}

function onColoniaChange(option: SimpleOption | null): void {
    selectedColonia.value = option;
    emit('update:colonia', option?.name ?? '');
}

function onMunicipalityCreated(payload: Record<string, unknown>): void {
    if (!selectedState.value) {
        return;
    }

    const created = payload as { id: number; name: string };
    results.value = [
        ...results.value,
        {
            id: Date.now(),
            code: props.postalCode ?? '',
            settlement: props.colonia || 'Sin colonia',
            state: { id: Number(selectedState.value.id), name: selectedState.value.name },
            municipality: { id: created.id, name: created.name },
        },
    ];
    selectedCity.value = { id: created.id, name: created.name };
    emit('update:city', created.name);
}

watch(
    () => props.postalCode ?? '',
    (value) => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => fetchPostalData(value), 300);
    },
    { immediate: true },
);
</script>

<template>
    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
        <div class="grid gap-1">
            <FloatLabel variant="on">
                <IconField>
                    <InputIcon>
                        <MapPinned class="h-4 w-4 text-muted-foreground" />
                    </InputIcon>
                    <InputText
                        id="postal_code"
                        :model-value="props.postalCode ?? ''"
                        maxlength="5"
                        class="w-full"
                        @update:model-value="emit('update:postalCode', String($event ?? ''))"
                    />
                </IconField>
                <label for="postal_code">Código postal</label>
            </FloatLabel>
            <InputError :message="props.errors?.postal_code" />
        </div>

        <div class="grid gap-1">
            <FloatLabel variant="on">
                <CascadeSelect
                    :model-value="selectedState"
                    :options="stateOptions"
                    option-label="name"
                    option-group-label="name"
                    :option-group-children="['items']"
                    input-id="state"
                    class="w-full"
                    :loading="loading"
                    @update:model-value="onStateChange($event)"
                />
                <label for="state">Estado</label>
            </FloatLabel>
            <InputError :message="props.errors?.state" />
        </div>

        <div class="grid gap-1">
            <div class="flex gap-2">
                <div class="min-w-0 flex-1">
                    <FloatLabel variant="on">
                        <CascadeSelect
                            :model-value="selectedCity"
                            :options="cityOptions"
                            option-label="name"
                            option-group-label="name"
                            :option-group-children="['items']"
                            input-id="city"
                            class="w-full"
                            :disabled="!selectedState"
                            @update:model-value="onCityChange($event)"
                        />
                        <label for="city">Ciudad o municipio</label>
                    </FloatLabel>
                </div>

                <InlineCatalogCreateDialog
                    v-if="selectedState"
                    :endpoint="patientCatalogRoutes.storeMunicipality(clinic).url"
                    :payload="{ state_id: selectedState.id }"
                    title="Nuevo municipio"
                    description="Agrega un municipio propio de la clínica para este estado."
                    button-label="Agregar municipio"
                    @saved="onMunicipalityCreated"
                />
            </div>
            <InputError :message="props.errors?.city" />
        </div>

        <div class="grid gap-1">
            <FloatLabel variant="on">
                <CascadeSelect
                    :model-value="selectedColonia"
                    :options="coloniaOptions"
                    option-label="name"
                    option-group-label="name"
                    :option-group-children="['items']"
                    input-id="colonia"
                    class="w-full"
                    :disabled="!selectedCity"
                    @update:model-value="onColoniaChange($event)"
                />
                <label for="colonia">Colonia</label>
            </FloatLabel>
            <InputError :message="props.errors?.colonia" />
        </div>
    </div>
</template>
