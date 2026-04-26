<?php

use App\Domain\Catalog\Geographic\Models\Country;
use App\Domain\Catalog\Geographic\Models\Municipality;
use App\Domain\Catalog\Geographic\Models\State;
use App\Domain\Catalog\Veterinary\Models\Breed;
use App\Domain\Catalog\Veterinary\Models\PelageColor;
use App\Domain\Catalog\Veterinary\Models\Species;
use App\Domain\Catalog\Veterinary\Models\Temperament;

test('clinic admin can create a clinic municipality', function () {
    [$clinic, $branch] = task03ClinicContext();
    $admin = task03ClinicAdmin($clinic, $branch);
    $country = Country::query()->create([
        'name' => 'Mexico',
        'iso2' => 'MX',
        'iso3' => 'MEX',
        'phone_code' => '52',
        'is_active' => true,
    ]);
    $state = State::query()->create([
        'country_id' => $country->id,
        'name' => 'Jalisco',
        'code' => 'JAL',
        'inegi_code' => '14',
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->postJson(task03ClinicRoute('clinic.api.catalog.municipalities.store', $clinic), [
            'state_id' => $state->id,
            'name' => 'Nuevo Municipio',
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Nuevo Municipio');

    $municipality = Municipality::query()->where('name', 'Nuevo Municipio')->first();

    expect($municipality)
        ->not->toBeNull()
        ->and($municipality?->clinic_id)->toBe($clinic->id);
});

test('clinic admin can create a clinic pelage color', function () {
    [$clinic, $branch] = task03ClinicContext();
    $admin = task03ClinicAdmin($clinic, $branch);

    $this->actingAs($admin)
        ->postJson(task03ClinicRoute('clinic.api.catalog.pelage-colors.store', $clinic), [
            'name' => 'Canela',
            'hex' => '#C68642',
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Canela');

    $pelageColor = PelageColor::query()->where('name', 'Canela')->first();

    expect($pelageColor)
        ->not->toBeNull()
        ->and($pelageColor?->clinic_id)->toBe($clinic->id);
});

test('clinic admin can create clinic species, breed and temperament', function () {
    [$clinic, $branch] = task03ClinicContext();
    $admin = task03ClinicAdmin($clinic, $branch);

    $speciesResponse = $this->actingAs($admin)
        ->postJson(task03ClinicRoute('clinic.api.catalog.species.store', $clinic), [
            'name' => 'Hurón',
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Hurón');

    $speciesId = (int) $speciesResponse->json('data.id');

    $this->actingAs($admin)
        ->postJson(task03ClinicRoute('clinic.api.catalog.breeds.store', $clinic), [
            'species_id' => $speciesId,
            'name' => 'Albino',
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Albino');

    $this->actingAs($admin)
        ->postJson(task03ClinicRoute('clinic.api.catalog.temperaments.store', $clinic), [
            'name' => 'Curioso',
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Curioso');

    $species = Species::query()->find($speciesId);
    $breed = Breed::query()->where('name', 'Albino')->first();
    $temperament = Temperament::query()->where('name', 'Curioso')->first();

    expect($species)
        ->not->toBeNull()
        ->and($species?->clinic_id)->toBe($clinic->id)
        ->and($breed)->not->toBeNull()
        ->and($breed?->clinic_id)->toBe($clinic->id)
        ->and($breed?->species_id)->toBe($speciesId)
        ->and($temperament)->not->toBeNull()
        ->and($temperament?->clinic_id)->toBe($clinic->id);
});
