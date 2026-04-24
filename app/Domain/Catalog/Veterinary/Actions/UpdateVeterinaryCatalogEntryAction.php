<?php

namespace App\Domain\Catalog\Veterinary\Actions;

use App\Domain\Catalog\Veterinary\Enums\CatalogType;
use Illuminate\Database\Eloquent\Model;

class UpdateVeterinaryCatalogEntryAction
{
    /** @param array<string, mixed> $data */
    public function handle(int $id, array $data): Model
    {
        $type = CatalogType::from($data['type']);
        $modelClass = $type->modelClass();

        /** @var Model $entry */
        $entry = $modelClass::withoutGlobalScopes()->findOrFail($id);

        $entry->fill(array_filter([
            'name' => $data['name'] ?? null,
            'icon' => $data['icon'] ?? null,
            'hex' => $data['hex'] ?? null,
            'sort_order' => $data['sort_order'] ?? null,
        ], fn ($v) => $v !== null));

        $entry->save();

        return $entry;
    }
}
