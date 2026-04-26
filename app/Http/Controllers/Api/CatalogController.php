<?php

namespace App\Http\Controllers\Api;

use App\Domain\Catalog\Geographic\Models\Country;
use App\Domain\Catalog\Geographic\Models\Municipality;
use App\Domain\Catalog\Geographic\Models\PostalCode;
use App\Domain\Catalog\Geographic\Models\State;
use App\Domain\Catalog\Veterinary\Models\Breed;
use App\Domain\Catalog\Veterinary\Models\PelageColor;
use App\Domain\Catalog\Veterinary\Models\PetSize;
use App\Domain\Catalog\Veterinary\Models\Species;
use App\Domain\Catalog\Veterinary\Models\Temperament;
use App\Http\Controllers\Controller;
use App\Http\Resources\Catalog\CatalogResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class CatalogController extends Controller
{
    private function like(): string
    {
        return DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
    }

    public function countries(Request $request): AnonymousResourceCollection
    {
        $q = $request->string('q');
        $limit = min($request->integer('limit', 30), 100);

        $query = Country::query()->where('is_active', true);

        if ($q->isNotEmpty()) {
            $query->where('name', $this->like(), "%{$q}%");
        }

        return CatalogResource::collection($query->orderBy('name')->limit($limit)->get());
    }

    public function states(Request $request): AnonymousResourceCollection
    {
        $q = $request->string('q');
        $limit = min($request->integer('limit', 30), 100);
        $countryId = $request->integer('parent_id');

        $query = State::query()->where('is_active', true);

        if ($countryId) {
            $query->where('country_id', $countryId);
        }

        if ($q->isNotEmpty()) {
            $query->where('name', $this->like(), "%{$q}%");
        }

        return CatalogResource::collection($query->orderBy('name')->limit($limit)->get());
    }

    public function municipalities(Request $request): AnonymousResourceCollection
    {
        $q = $request->string('q');
        $limit = min($request->integer('limit', 30), 100);
        $stateId = $request->integer('parent_id');

        $query = Municipality::query()->where('is_active', true);

        if ($stateId) {
            $query->where('state_id', $stateId);
        }

        if ($q->isNotEmpty()) {
            $query->where('name', $this->like(), "%{$q}%");
        }

        return CatalogResource::collection($query->orderBy('name')->limit($limit)->get());
    }

    public function postalCodes(Request $request): AnonymousResourceCollection
    {
        $q = $request->string('q');
        $limit = min($request->integer('limit', 30), 100);

        $query = PostalCode::query()->with(['state:id,name', 'municipality:id,name']);

        if ($q->length() === 5 && ctype_digit($q->value())) {
            $query->where('code', $q->value());
        } else {
            $query->where('settlement', $this->like(), "%{$q}%");
        }

        return CatalogResource::collection($query->limit($limit)->get());
    }

    public function species(Request $request): AnonymousResourceCollection
    {
        $q = $request->string('q');
        $limit = min($request->integer('limit', 30), 100);

        $query = Species::query()->where('is_active', true);

        if ($q->isNotEmpty()) {
            $query->where('name', $this->like(), "%{$q}%");
        }

        return CatalogResource::collection($query->orderBy('sort_order')->orderBy('name')->limit($limit)->get());
    }

    public function breeds(Request $request): AnonymousResourceCollection
    {
        $q = $request->string('q');
        $limit = min($request->integer('limit', 30), 100);
        $speciesId = $request->integer('parent_id');

        $query = Breed::query()->where('is_active', true);

        if ($speciesId) {
            $query->where('species_id', $speciesId);
        }

        if ($q->isNotEmpty()) {
            $query->where('name', $this->like(), "%{$q}%");
        }

        return CatalogResource::collection($query->orderBy('name')->limit($limit)->get());
    }

    public function pelageColors(Request $request): AnonymousResourceCollection
    {
        $q = $request->string('q');
        $limit = min($request->integer('limit', 30), 100);

        $query = PelageColor::query()->where('is_active', true);

        if ($q->isNotEmpty()) {
            $query->where('name', $this->like(), "%{$q}%");
        }

        return CatalogResource::collection($query->orderBy('name')->limit($limit)->get());
    }

    public function petSizes(Request $request): AnonymousResourceCollection
    {
        $q = $request->string('q');
        $limit = min($request->integer('limit', 30), 100);

        $query = PetSize::query()->where('is_active', true);

        if ($q->isNotEmpty()) {
            $query->where('name', $this->like(), "%{$q}%");
        }

        return CatalogResource::collection($query->orderBy('sort_order')->orderBy('name')->limit($limit)->get());
    }

    public function temperaments(Request $request): AnonymousResourceCollection
    {
        $q = $request->string('q');
        $limit = min($request->integer('limit', 30), 100);

        $query = Temperament::query()->where('is_active', true);

        if ($q->isNotEmpty()) {
            $query->where('name', $this->like(), "%{$q}%");
        }

        return CatalogResource::collection($query->orderBy('name')->limit($limit)->get());
    }
}
