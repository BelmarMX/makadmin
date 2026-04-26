<?php

namespace App\Http\Controllers\Clinic\Api;

use App\Domain\Patient\Models\Client;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Client::class);

        $term = $request->string('q')->trim()->toString();

        abort_if(mb_strlen($term) < 2, 422, 'Mínimo 2 caracteres.');

        $results = Client::query()
            ->active()
            ->search($term)
            ->withCount(['patients' => fn ($query) => $query->where('is_active', true)])
            ->limit(10)
            ->get(['id', 'name', 'email', 'phone']);

        return response()->json($results);
    }
}
