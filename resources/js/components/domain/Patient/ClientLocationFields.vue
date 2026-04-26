<script setup lang="ts">
import { MapPinned, Plus, Save } from 'lucide-vue-next';
import { computed, nextTick, ref, watch } from 'vue';
import FloatLabel from 'primevue/floatlabel';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
const selectedColonia = ref<string | null>(null);
const coloniaDialog = ref(false);
const newColonia = ref('');
let debounceTimer: ReturnType<typeof setTimeout>;

const stateOptions = computed<SimpleOption[]>(() => {
    const map = new Map<number, SimpleOption>();
    for (const r of results.value) {
        map.set(r.state.id, { id: r.state.id, name: r.state.name });
    }
    return [...map.values()];
});

const cityOptions = computed<SimpleOption[]>(() => {
    if (!selectedState.value) return [];
    const map = new Map<number, SimpleOption>();
    for (const r of results.value) {
        if (r.state.id === selectedState.value.id) {
            map.set(r.municipality.id, {
                id: r.municipality.id,
                name: r.municipality.name,
            });
        }
    }
    return [...map.values()];
});

const coloniaOptions = computed<string[]>(() => {
    if (!selectedState.value || !selectedCity.value) return [];
    const set = new Set<string>();
    for (const r of results.value) {
        if (
            r.state.id === selectedState.value.id &&
            r.municipality.id === selectedCity.value.id
        ) {
            set.add(r.settlement);
        }
    }
    return [...set];
});

async function fetchPostalData(postalCode: string): Promise<void> {
    const wasAlreadySet = selectedState.value !== null;

    selectedState.value = null;
    selectedCity.value = null;
    selectedColonia.value = null;

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
        const data = (json.data ?? []) as PostalCodeResult[];
        results.value = data;

        if (data.length === 0) return;

        await nextTick();

        const states = stateOptions.value;
        if (states.length === 1) {
            selectedState.value = states[0];
            emit('update:state', states[0].name);
            await nextTick();

            const cities = cityOptions.value;
            if (cities.length === 1) {
                selectedCity.value = cities[0];
                emit('update:city', cities[0].name);
            }
        }
    } finally {
        loading.value = false;
    }
}

function onStateChange(option: SimpleOption | null): void {
    selectedCity.value = null;
    selectedColonia.value = null;
    emit('update:state', option?.name ?? '');
}

function onCityChange(option: SimpleOption | null): void {
    selectedColonia.value = null;
    emit('update:city', option?.name ?? '');
}

function onColoniaChange(value: string | null): void {
    selectedColonia.value = value;
    emit('update:colonia', value ?? '');
}

function onMunicipalityCreated(payload: Record<string, unknown>): void {
    if (!selectedState.value) return;

    const created = payload as { id: number; name: string };
    results.value = [
        ...results.value,
        {
            id: Date.now(),
            code: props.postalCode ?? '',
            settlement: props.colonia || 'Sin colonia',
            state: {
                id: Number(selectedState.value.id),
                name: selectedState.value.name,
            },
            municipality: { id: created.id, name: created.name },
        },
    ];
    selectedCity.value = { id: created.id, name: created.name };
    emit('update:city', created.name);
}

function openColoniaDialog(): void {
    newColonia.value = '';
    coloniaDialog.value = true;
}

function confirmColonia(): void {
    const name = newColonia.value.trim();
    if (!name) return;
    emit('update:colonia', name);
    selectedColonia.value = name;
    coloniaDialog.value = false;
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
                        @update:model-value="
                            emit('update:postalCode', String($event ?? ''))
                        "
                    />
                </IconField>
                <label for="postal_code">Código postal</label>
            </FloatLabel>
            <InputError :message="props.errors?.postal_code" />
        </div>

        <div class="grid gap-1">
            <FloatLabel variant="on">
                <Select
                    v-model="selectedState"
                    :options="stateOptions"
                    option-label="name"
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
                        <Select
                            v-model="selectedCity"
                            :options="cityOptions"
                            :loading="loading"
                            option-label="name"
                            input-id="city"
                            class="w-full"
                            :disabled="!selectedState"
                            @update:model-value="onCityChange($event)"
                        />
                        <label for="city">Ciudad o municipio</label>
                    </FloatLabel>
                </div>

                <InlineCatalogCreateDialog
                    :endpoint="
                        patientCatalogRoutes.storeMunicipality(clinic).url
                    "
                    :payload="
                        selectedState ? { state_id: selectedState.id } : {}
                    "
                    title="Nuevo municipio"
                    description="Agrega un municipio propio de la clínica para este estado."
                    button-label="Agregar municipio"
                    @saved="onMunicipalityCreated"
                />
            </div>
            <InputError :message="props.errors?.city" />
        </div>

        <div class="grid gap-1">
            <div class="flex gap-2">
                <div class="min-w-0 flex-1">
                    <FloatLabel variant="on">
                        <Select
                            v-model="selectedColonia"
                            :options="coloniaOptions"
                            :loading="loading"
                            input-id="colonia"
                            class="w-full"
                            :disabled="!selectedCity"
                            @update:model-value="onColoniaChange($event)"
                        />
                        <label for="colonia">Colonia</label>
                    </FloatLabel>
                </div>

                <Button
                    variant="outline"
                    size="icon-sm"
                    :disabled="!selectedCity"
                    v-tooltip.left="'Agregar colonia'"
                    @click="openColoniaDialog"
                >
                    <Plus class="h-4 w-4" />
                </Button>
            </div>
            <InputError :message="props.errors?.colonia" />
        </div>
    </div>

    <Dialog :open="coloniaDialog" @update:open="coloniaDialog = $event">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Agregar colonia</DialogTitle>
            </DialogHeader>
            <div class="space-y-4 py-2">
                <div class="space-y-2">
                    <Label for="new-colonia">Nombre de la colonia</Label>
                    <Input
                        id="new-colonia"
                        v-model="newColonia"
                        placeholder="Centro"
                    />
                </div>
            </div>
            <DialogFooter>
                <Button variant="outline" @click="coloniaDialog = false"
                    >Cancelar</Button
                >
                <Button :disabled="!newColonia.trim()" @click="confirmColonia">
                    <Save class="h-4 w-4" />
                    Agregar
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
