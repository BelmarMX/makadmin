<?php

namespace App\Http\Controllers\Clinic\Api;

use App\Domain\Patient\Models\Patient;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Patient::class);

        $term = $request->string('q')->trim()->toString();

        abort_if(mb_strlen($term) < 2, 422, 'Mínimo 2 caracteres.');

        $results = Patient::query()
            ->active()
            ->search($term)
            ->with(['client:id,name,phone', 'species:id,name'])
            ->limit(10)
            ->get(['id', 'name', 'microchip', 'client_id', 'species_id', 'photo_path', 'is_active', 'is_deceased']);

        return response()->json($results);
    }
}
