<?php

namespace App\Domain\Catalog\Veterinary\Actions;

use App\Domain\Catalog\Veterinary\Enums\CatalogType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CreateVeterinaryCatalogEntryAction
{
    /** @param array<string, mixed> $data */
    public function handle(array $data): Model
    {
        $type = CatalogType::from($data['type']);
        $modelClass = $type->modelClass();

        $attributes = [
            'name' => $data['name'],
            'slug' => $data['slug'] ?? Str::slug($data['name']),
            'is_system' => true,
            'is_active' => true,
        ];

        if (isset($data['icon'])) {
            $attributes['icon'] = $data['icon'];
        }

        if (isset($data['hex'])) {
            $attributes['hex'] = $data['hex'];
        }

        if (isset($data['sort_order'])) {
            $attributes['sort_order'] = $data['sort_order'];
        }

        if (isset($data['species_id'])) {
            $attributes['species_id'] = $data['species_id'];
        }

        return $modelClass::asGlobal()->create($attributes);
    }
}
