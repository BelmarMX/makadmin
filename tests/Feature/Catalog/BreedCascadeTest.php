<?php

use App\Domain\Catalog\Veterinary\Models\Breed;
use App\Domain\Catalog\Veterinary\Models\Species;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['branding.apex_domain' => 'vetfollow.test']);
});

test('api breeds endpoint filters by species_id', function () {
    $user = User::factory()->create();

    $canino = Species::withoutGlobalScopes()->create([
        'name' => 'Canino', 'slug' => 'canino',
        'clinic_id' => null, 'is_system' => true, 'is_active' => true,
    ]);
    $felino = Species::withoutGlobalScopes()->create([
        'name' => 'Felino', 'slug' => 'felino',
        'clinic_id' => null, 'is_system' => true, 'is_active' => true,
    ]);

    Breed::withoutGlobalScopes()->create([
        'name' => 'Labrador', 'slug' => 'labrador', 'species_id' => $canino->id,
        'clinic_id' => null, 'is_system' => true, 'is_active' => true,
    ]);
    Breed::withoutGlobalScopes()->create([
        'name' => 'Persa', 'slug' => 'persa', 'species_id' => $felino->id,
        'clinic_id' => null, 'is_system' => true, 'is_active' => true,
    ]);

    $this->actingAs($user)
        ->getJson(route('api.catalog.breeds', ['parent_id' => $canino->id]))
        ->assertOk()
        ->assertJsonFragment(['name' => 'Labrador'])
        ->assertJsonMissing(['name' => 'Persa']);
});

test('archiving a species soft-deletes it but leaves breeds', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);

    $species = Species::withoutGlobalScopes()->create([
        'name' => 'TempEspecie', 'slug' => 'temp-especie',
        'clinic_id' => null, 'is_system' => true, 'is_active' => true,
    ]);
    $breed = Breed::withoutGlobalScopes()->create([
        'name' => 'TempRaza', 'slug' => 'temp-raza', 'species_id' => $species->id,
        'clinic_id' => null, 'is_system' => true, 'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.catalog.destroy', $species->id), ['type' => 'species'])
        ->assertRedirect();

    expect(Species::withoutGlobalScopes()->withTrashed()->find($species->id)?->deleted_at)->not->toBeNull();
    expect(Breed::withoutGlobalScopes()->find($breed->id))->not->toBeNull();
});
