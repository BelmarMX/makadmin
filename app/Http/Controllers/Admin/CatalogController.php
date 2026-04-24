<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Catalog\Veterinary\Actions\ArchiveVeterinaryCatalogEntryAction;
use App\Domain\Catalog\Veterinary\Actions\CreateVeterinaryCatalogEntryAction;
use App\Domain\Catalog\Veterinary\Actions\UpdateVeterinaryCatalogEntryAction;
use App\Domain\Catalog\Veterinary\Enums\CatalogType;
use App\Domain\Catalog\Veterinary\Models\Breed;
use App\Domain\Catalog\Veterinary\Models\PelageColor;
use App\Domain\Catalog\Veterinary\Models\PetSize;
use App\Domain\Catalog\Veterinary\Models\Species;
use App\Domain\Catalog\Veterinary\Models\Temperament;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CatalogController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Species::class);

        return Inertia::render('Admin/Catalog/Index', [
            'species' => Species::withoutGlobalScopes()->where('is_system', true)->orderBy('sort_order')->get(),
            'breeds' => Breed::withoutGlobalScopes()->where('is_system', true)->with('species:id,name')->orderBy('species_id')->orderBy('name')->get(),
            'pelageColors' => PelageColor::withoutGlobalScopes()->where('is_system', true)->orderBy('name')->get(),
            'petSizes' => PetSize::withoutGlobalScopes()->where('is_system', true)->orderBy('sort_order')->get(),
            'temperaments' => Temperament::withoutGlobalScopes()->where('is_system', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, CreateVeterinaryCatalogEntryAction $action): RedirectResponse
    {
        $this->authorize('create', Species::class);

        $validated = $request->validate([
            'type' => ['required', 'string', 'in:'.implode(',', CatalogType::values())],
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:100'],
            'icon' => ['nullable', 'string', 'max:40'],
            'hex' => ['nullable', 'string', 'size:7', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'species_id' => ['nullable', 'integer', 'exists:species,id'],
        ]);

        $action->handle($validated);

        return back()->with('success', 'Entrada creada correctamente.');
    }

    public function update(Request $request, int $id, UpdateVeterinaryCatalogEntryAction $action): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:'.implode(',', CatalogType::values())],
            'name' => ['required', 'string', 'max:100'],
            'icon' => ['nullable', 'string', 'max:40'],
            'hex' => ['nullable', 'string', 'size:7', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $action->handle($id, $validated);

        return back()->with('success', 'Entrada actualizada.');
    }

    public function destroy(int $id, Request $request, ArchiveVeterinaryCatalogEntryAction $action): RedirectResponse
    {
        $type = $request->validate(['type' => ['required', 'string', 'in:'.implode(',', CatalogType::values())]])['type'];

        $action->handle($id, $type);

        return back()->with('success', 'Entrada archivada.');
    }
}
