<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Barcode, Save, Scale, Tag } from 'lucide-vue-next';
import Checkbox from 'primevue/checkbox';
import DatePicker from 'primevue/datepicker';
import FloatLabel from 'primevue/floatlabel';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import { computed, ref } from 'vue';
import { toast } from '@/lib/toast';
import InlineCatalogCreateDialog from '@/components/domain/Patient/InlineCatalogCreateDialog.vue';
import InputError from '@/components/InputError.vue';
import PatientPhotoUpload from '@/components/domain/Patient/PatientPhotoUpload.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import * as clientRoutes from '@/actions/App/Http/Controllers/Clinic/ClientController';
import * as patientCatalogRoutes from '@/actions/App/Http/Controllers/Clinic/Api/PatientCatalogController';
import * as patientRoutes from '@/actions/App/Http/Controllers/Clinic/PatientController';
import { clinicSlug } from '@/composables/useClinicSlug';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    client: { id: number; name: string };
    species: Array<{ id: number; name: string }>;
    breeds: Array<{ id: number; name: string; species_id: number }>;
    temperaments: Array<{ id: number; name: string }>;
    coatColors: Array<{ id: number; name: string; hex?: string | null }>;
    sexOptions: Array<{ value: string; label: string }>;
    sizeOptions: Array<{ value: string; label: string }>;
}>();

const clinic = clinicSlug();
const preview = ref<string | null>(null);
const species = ref([...props.species]);
const breeds = ref([...props.breeds]);
const temperaments = ref([...props.temperaments]);
const coatColors = ref([...props.coatColors]);
const form = useForm<{
    name: string;
    sex: string;
    species_id: number | null;
    breed_id: number | null;
    temperament_id: number | null;
    coat_color_id: number | null;
    birth_date: string;
    microchip: string;
    size: string | null;
    weight_kg: string;
    notes: string;
    is_sterilized: boolean;
    is_deceased: boolean;
    deceased_at: string;
    photo: File | null;
}>({
    name: '',
    sex: 'unknown',
    species_id: null,
    breed_id: null,
    temperament_id: null,
    coat_color_id: null,
    birth_date: '',
    microchip: '',
    size: null,
    weight_kg: '',
    notes: '',
    is_sterilized: false,
    is_deceased: false,
    deceased_at: '',
    photo: null,
});

const filteredBreeds = computed(() => breeds.value.filter((breed) => !form.species_id || breed.species_id === form.species_id));

function formatDate(value: Date | null): string {
    if (!value) {
        return '';
    }

    const year = value.getFullYear();
    const month = String(value.getMonth() + 1).padStart(2, '0');
    const day = String(value.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

const birthDateModel = computed<Date | null>({
    get: () => (form.birth_date ? new Date(`${form.birth_date}T00:00:00`) : null),
    set: (value) => {
        form.birth_date = formatDate(value);
    },
});

const deceasedAtModel = computed<Date | null>({
    get: () => (form.deceased_at ? new Date(`${form.deceased_at}T00:00:00`) : null),
    set: (value) => {
        form.deceased_at = formatDate(value);
    },
});

function onPelageColorCreated(payload: Record<string, unknown>): void {
    const created = payload as { id: number; name: string; hex?: string | null };
    coatColors.value = [...coatColors.value, created].sort((left, right) => left.name.localeCompare(right.name));
    form.coat_color_id = created.id;
}

function onSpeciesCreated(payload: Record<string, unknown>): void {
    const created = payload as { id: number; name: string };
    species.value = [...species.value, created].sort((left, right) => left.name.localeCompare(right.name));
    form.species_id = created.id;
    form.breed_id = null;
}

function onBreedCreated(payload: Record<string, unknown>): void {
    const created = payload as { id: number; name: string; species_id: number };
    breeds.value = [...breeds.value, created].sort((left, right) => left.name.localeCompare(right.name));
    form.breed_id = created.id;
}

function onTemperamentCreated(payload: Record<string, unknown>): void {
    const created = payload as { id: number; name: string };
    temperaments.value = [...temperaments.value, created].sort((left, right) => left.name.localeCompare(right.name));
    form.temperament_id = created.id;
}

function submit(): void {
    form.post(patientRoutes.store({ clinic, client: props.client.id }).url, {
        forceFormData: true,
        onSuccess: () => toast.success('Paciente registrado'),
        onError: () => toast.error('Error al registrar paciente'),
    });
}
</script>

<template>
    <Head title="Nueva mascota" />

    <form class="space-y-6" @submit.prevent="submit">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Nueva mascota</h1>
                <p class="text-sm text-muted-foreground">
                    Tutor: {{ client.name }}
                </p>
            </div>
            <div class="flex gap-2">
                <Button variant="outline" as-child v-tooltip.bottom="'Volver al tutor'">
                    <Link :href="clientRoutes.show({ clinic, client: client.id }).url">
                        <ArrowLeft class="h-4 w-4" />
                    </Link>
                </Button>
                <Button :disabled="form.processing" v-ripple>
                    <Save class="h-4 w-4" />
                    Guardar
                </Button>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-[12rem_1fr]">
            <PatientPhotoUpload
                v-model="form.photo"
                :preview="preview"
                :error="form.errors.photo"
                label="Foto del paciente"
                @update:preview="preview = $event"
            />

            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                <div class="grid gap-1">
                    <FloatLabel variant="on">
                        <IconField>
                            <InputIcon>
                                <Tag class="h-4 w-4 text-muted-foreground" />
                            </InputIcon>
                            <InputText id="name" v-model="form.name" class="w-full" />
                        </IconField>
                        <label for="name">Nombre</label>
                    </FloatLabel>
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-1">
                    <FloatLabel variant="on">
                        <Select
                            v-model="form.sex"
                            :options="sexOptions"
                            option-label="label"
                            option-value="value"
                            input-id="sex"
                            class="w-full"
                        />
                        <label for="sex">Sexo</label>
                    </FloatLabel>
                    <InputError :message="form.errors.sex" />
                </div>

                <div class="grid gap-1">
                    <div class="flex gap-2">
                        <div class="min-w-0 flex-1">
                            <FloatLabel variant="on">
                                <Select
                                    v-model="form.species_id"
                                    :options="species"
                                    option-label="name"
                                    option-value="id"
                                    input-id="species"
                                    class="w-full"
                                    @change="form.breed_id = null"
                                />
                                <label for="species">Especie</label>
                            </FloatLabel>
                        </div>
                        <InlineCatalogCreateDialog
                            :endpoint="patientCatalogRoutes.storeSpecies(clinic).url"
                            title="Nueva especie"
                            description="Agrega una especie propia de la clínica."
                            button-label="Agregar especie"
                            @saved="onSpeciesCreated"
                        />
                    </div>
                    <InputError :message="form.errors.species_id" />
                </div>

                <div class="grid gap-1">
                    <div class="flex gap-2">
                        <div class="min-w-0 flex-1">
                            <FloatLabel variant="on">
                                <Select
                                    v-model="form.breed_id"
                                    :options="filteredBreeds"
                                    option-label="name"
                                    option-value="id"
                                    input-id="breed"
                                    class="w-full"
                                    :disabled="!form.species_id"
                                />
                                <label for="breed">Raza</label>
                            </FloatLabel>
                        </div>
                        <InlineCatalogCreateDialog
                            :endpoint="patientCatalogRoutes.storeBreed(clinic).url"
                            :payload="{ species_id: form.species_id }"
                            title="Nueva raza"
                            description="Agrega una raza propia de la clínica para la especie seleccionada."
                            button-label="Agregar raza"
                            :disabled="!form.species_id"
                            @saved="onBreedCreated"
                        />
                    </div>
                    <InputError :message="form.errors.breed_id" />
                </div>

                <div class="grid gap-1">
                    <div class="flex gap-2">
                        <div class="min-w-0 flex-1">
                            <FloatLabel variant="on">
                                <Select
                                    v-model="form.temperament_id"
                                    :options="temperaments"
                                    option-label="name"
                                    option-value="id"
                                    input-id="temperament"
                                    class="w-full"
                                />
                                <label for="temperament">Temperamento</label>
                            </FloatLabel>
                        </div>
                        <InlineCatalogCreateDialog
                            :endpoint="patientCatalogRoutes.storeTemperament(clinic).url"
                            title="Nuevo temperamento"
                            description="Agrega un temperamento propio de la clínica."
                            button-label="Agregar temperamento"
                            @saved="onTemperamentCreated"
                        />
                    </div>
                    <InputError :message="form.errors.temperament_id" />
                </div>

                <div class="grid gap-1">
                    <div class="flex gap-2">
                        <div class="min-w-0 flex-1">
                            <FloatLabel variant="on">
                                <Select
                                    v-model="form.coat_color_id"
                                    :options="coatColors"
                                    option-label="name"
                                    option-value="id"
                                    input-id="coat_color"
                                    class="w-full"
                                />
                                <label for="coat_color">Color de pelaje</label>
                            </FloatLabel>
                        </div>
                        <InlineCatalogCreateDialog
                            :endpoint="patientCatalogRoutes.storePelageColor(clinic).url"
                            title="Nuevo color de pelaje"
                            description="Agrega un color propio de la clínica para usarlo en pacientes."
                            button-label="Agregar color"
                            :show-hex="true"
                            @saved="onPelageColorCreated"
                        />
                    </div>
                    <InputError :message="form.errors.coat_color_id" />
                </div>

                <div class="grid gap-1">
                    <FloatLabel variant="on">
                        <DatePicker
                            id="birth_date"
                            v-model="birthDateModel"
                            show-icon
                            fluid
                            icon-display="input"
                            date-format="yy-mm-dd"
                            :manual-input="false"
                            input-class="w-full"
                            class="w-full"
                        />
                        <label for="birth_date">Fecha de nacimiento</label>
                    </FloatLabel>
                    <InputError :message="form.errors.birth_date" />
                </div>

                <div class="grid gap-1">
                    <FloatLabel variant="on">
                        <IconField>
                            <InputIcon>
                                <Barcode class="h-4 w-4 text-muted-foreground" />
                            </InputIcon>
                            <InputText id="microchip" v-model="form.microchip" class="w-full" />
                        </IconField>
                        <label for="microchip">Microchip</label>
                    </FloatLabel>
                    <InputError :message="form.errors.microchip" />
                </div>

                <div class="grid gap-1">
                    <FloatLabel variant="on">
                        <Select
                            v-model="form.size"
                            :options="sizeOptions"
                            option-label="label"
                            option-value="value"
                            input-id="size"
                            class="w-full"
                        />
                        <label for="size">Tamaño</label>
                    </FloatLabel>
                    <InputError :message="form.errors.size" />
                </div>

                <div class="grid gap-1">
                    <FloatLabel variant="on">
                        <IconField>
                            <InputIcon>
                                <Scale class="h-4 w-4 text-muted-foreground" />
                            </InputIcon>
                            <InputText id="weight_kg" v-model="form.weight_kg" type="number" step="0.01" class="w-full" />
                        </IconField>
                        <label for="weight_kg">Peso (kg)</label>
                    </FloatLabel>
                    <InputError :message="form.errors.weight_kg" />
                </div>

                <div class="flex items-center gap-3 rounded-lg border border-border bg-card px-3 py-2">
                    <Checkbox v-model="form.is_sterilized" binary input-id="is_sterilized" />
                    <label for="is_sterilized" class="text-sm font-medium text-foreground">Esterilizado</label>
                </div>

                <div class="flex items-center gap-3 rounded-lg border border-border bg-card px-3 py-2">
                    <Checkbox v-model="form.is_deceased" binary input-id="is_deceased" />
                    <label for="is_deceased" class="text-sm font-medium text-foreground">Paciente fallecido</label>
                </div>

                <div v-if="form.is_deceased" class="grid gap-1">
                    <FloatLabel variant="on">
                        <DatePicker
                            id="deceased_at"
                            v-model="deceasedAtModel"
                            show-icon
                            fluid
                            icon-display="input"
                            date-format="yy-mm-dd"
                            :manual-input="false"
                            input-class="w-full"
                            class="w-full"
                        />
                        <label for="deceased_at">Fecha de fallecimiento</label>
                    </FloatLabel>
                    <InputError :message="form.errors.deceased_at" />
                </div>

                <div class="grid gap-1 md:col-span-2 xl:col-span-3">
                    <FloatLabel variant="on">
                        <Textarea id="notes" v-model="form.notes" rows="4" class="w-full" />
                        <label for="notes">Notas</label>
                    </FloatLabel>
                    <InputError :message="form.errors.notes" />
                </div>
            </div>
        </div>
    </form>
</template>
