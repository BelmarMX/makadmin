<?php

namespace App\Http\Resources\Catalog;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CatalogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->when(isset($this->slug), fn () => $this->slug),
            'icon' => $this->when(isset($this->icon), fn () => $this->icon),
            'hex' => $this->when(isset($this->hex), fn () => $this->hex),
            'sort_order' => $this->when(isset($this->sort_order), fn () => $this->sort_order),
            'is_system' => $this->when(isset($this->is_system), fn () => $this->is_system),
            'is_active' => $this->when(isset($this->is_active), fn () => $this->is_active),
            'weight_min_kg' => $this->when(isset($this->weight_min_kg), fn () => $this->weight_min_kg),
            'weight_max_kg' => $this->when(isset($this->weight_max_kg), fn () => $this->weight_max_kg),
            'species_id' => $this->when(isset($this->species_id), fn () => $this->species_id),
            'state' => $this->when($this->relationLoaded('state'), fn () => ['id' => $this->state->id, 'name' => $this->state->name]),
            'municipality' => $this->when($this->relationLoaded('municipality'), fn () => ['id' => $this->municipality->id, 'name' => $this->municipality->name]),
            'code' => $this->when(isset($this->code), fn () => $this->code),
            'settlement' => $this->when(isset($this->settlement), fn () => $this->settlement),
            'settlement_type' => $this->when(isset($this->settlement_type), fn () => $this->settlement_type),
            'inegi_code' => $this->when(isset($this->inegi_code), fn () => $this->inegi_code),
            'iso2' => $this->when(isset($this->iso2), fn () => $this->iso2),
            'phone_code' => $this->when(isset($this->phone_code), fn () => $this->phone_code),
        ];
    }
}
