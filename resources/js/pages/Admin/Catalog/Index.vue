<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Tag } from 'lucide-vue-next';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import SpeciesManager from '@/pages/Admin/Catalog/SpeciesManager.vue';
import BreedsManager from '@/pages/Admin/Catalog/BreedsManager.vue';
import PelageColorsManager from '@/pages/Admin/Catalog/PelageColorsManager.vue';
import PetSizesManager from '@/pages/Admin/Catalog/PetSizesManager.vue';
import TemperamentsManager from '@/pages/Admin/Catalog/TemperamentsManager.vue';

defineOptions({ layout: AdminLayout });

export interface CatalogEntry {
    id: number;
    name: string;
    slug?: string;
    icon?: string;
    hex?: string;
    sort_order?: number;
    is_system: boolean;
    is_active: boolean;
    weight_min_kg?: number | null;
    weight_max_kg?: number | null;
    species_id?: number;
    species?: { id: number; name: string };
}

const props = defineProps<{
    species: CatalogEntry[];
    breeds: (CatalogEntry & { species: { id: number; name: string } })[];
    pelageColors: CatalogEntry[];
    petSizes: CatalogEntry[];
    temperaments: CatalogEntry[];
}>();

const activeTab = ref('species');
</script>

<template>
    <Head title="Catálogos Base" />

    <div class="space-y-6">
        <div class="flex items-center gap-3">
            <Tag class="h-6 w-6 text-primary" />
            <div>
                <h1 class="text-2xl font-bold text-foreground">Catálogos Base</h1>
                <p class="text-sm text-muted-foreground">Gestión de catálogos veterinarios del sistema.</p>
            </div>
        </div>

        <Tabs v-model="activeTab">
            <TabsList class="w-full justify-start">
                <TabsTrigger value="species">Especies</TabsTrigger>
                <TabsTrigger value="breeds">Razas</TabsTrigger>
                <TabsTrigger value="colors">Colores</TabsTrigger>
                <TabsTrigger value="sizes">Tamaños</TabsTrigger>
                <TabsTrigger value="temperaments">Temperamentos</TabsTrigger>
            </TabsList>

            <TabsContent value="species">
                <SpeciesManager :entries="species" />
            </TabsContent>
            <TabsContent value="breeds">
                <BreedsManager :entries="breeds" :species-list="species" />
            </TabsContent>
            <TabsContent value="colors">
                <PelageColorsManager :entries="pelageColors" />
            </TabsContent>
            <TabsContent value="sizes">
                <PetSizesManager :entries="petSizes" />
            </TabsContent>
            <TabsContent value="temperaments">
                <TemperamentsManager :entries="temperaments" />
            </TabsContent>
        </Tabs>
    </div>
</template>
