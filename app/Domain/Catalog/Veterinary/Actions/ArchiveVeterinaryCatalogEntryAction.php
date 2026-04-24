<?php

namespace App\Domain\Catalog\Veterinary\Actions;

use App\Domain\Catalog\Veterinary\Enums\CatalogType;

class ArchiveVeterinaryCatalogEntryAction
{
    public function handle(int $id, string $type): void
    {
        $catalogType = CatalogType::from($type);
        $modelClass = $catalogType->modelClass();

        $entry = $modelClass::withoutGlobalScopes()->findOrFail($id);
        $entry->delete();
    }
}
