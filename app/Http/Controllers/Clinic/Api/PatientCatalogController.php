<?php

namespace App\Http\Controllers\Clinic\Api;

use App\Domain\Catalog\Geographic\Actions\CreateClinicMunicipalityAction;
use App\Domain\Catalog\Veterinary\Actions\CreateClinicBreedAction;
use App\Domain\Catalog\Veterinary\Actions\CreateClinicPelageColorAction;
use App\Domain\Catalog\Veterinary\Actions\CreateClinicSpeciesAction;
use App\Domain\Catalog\Veterinary\Actions\CreateClinicTemperamentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patient\StoreBreedRequest;
use App\Http\Requests\Patient\StoreMunicipalityRequest;
use App\Http\Requests\Patient\StorePelageColorRequest;
use App\Http\Requests\Patient\StoreSpeciesRequest;
use App\Http\Requests\Patient\StoreTemperamentRequest;
use App\Http\Resources\Catalog\CatalogResource;
use Illuminate\Http\JsonResponse;

class PatientCatalogController extends Controller
{
    public function storeSpecies(
        StoreSpeciesRequest $request,
        CreateClinicSpeciesAction $action,
    ): JsonResponse {
        $species = $action->handle((string) $request->validated('name'));

        return response()->json([
            'data' => CatalogResource::make($species),
        ], 201);
    }

    public function storeBreed(
        StoreBreedRequest $request,
        CreateClinicBreedAction $action,
    ): JsonResponse {
        $breed = $action->handle(
            speciesId: (int) $request->validated('species_id'),
            name: (string) $request->validated('name'),
        );

        return response()->json([
            'data' => CatalogResource::make($breed),
        ], 201);
    }

    public function storeTemperament(
        StoreTemperamentRequest $request,
        CreateClinicTemperamentAction $action,
    ): JsonResponse {
        $temperament = $action->handle((string) $request->validated('name'));

        return response()->json([
            'data' => CatalogResource::make($temperament),
        ], 201);
    }

    public function storeMunicipality(
        StoreMunicipalityRequest $request,
        CreateClinicMunicipalityAction $action,
    ): JsonResponse {
        $municipality = $action->handle(
            stateId: (int) $request->validated('state_id'),
            name: (string) $request->validated('name'),
        );

        return response()->json([
            'data' => CatalogResource::make($municipality),
        ], 201);
    }

    public function storePelageColor(
        StorePelageColorRequest $request,
        CreateClinicPelageColorAction $action,
    ): JsonResponse {
        $pelageColor = $action->handle(
            name: (string) $request->validated('name'),
            hex: $request->validated('hex'),
        );

        return response()->json([
            'data' => CatalogResource::make($pelageColor),
        ], 201);
    }
}
