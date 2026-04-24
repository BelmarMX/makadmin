<?php

use App\Domain\Catalog\Veterinary\Models\Species;
use App\Domain\Clinic\Models\Clinic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('clinic A cannot see clinic B own species', function () {
    $clinicA = Clinic::factory()->create();
    $clinicB = Clinic::factory()->create();

    $speciesB = Species::withoutGlobalScopes()->create([
        'name' => 'Only B', 'slug' => 'only-b', 'clinic_id' => $clinicB->id,
        'is_system' => false, 'is_active' => true,
    ]);

    app()->instance('current.clinic', $clinicA);

    expect(Species::find($speciesB->id))->toBeNull();
});

test('both clinics see global species', function () {
    $clinicA = Clinic::factory()->create();
    $clinicB = Clinic::factory()->create();

    $global = Species::withoutGlobalScopes()->create([
        'name' => 'Global Canino', 'slug' => 'canino-g', 'clinic_id' => null,
        'is_system' => true, 'is_active' => true,
    ]);

    app()->instance('current.clinic', $clinicA);
    expect(Species::find($global->id))->not->toBeNull();

    app()->instance('current.clinic', $clinicB);
    expect(Species::find($global->id))->not->toBeNull();
});

test('clinic cannot access other clinic species by ID via API', function () {
    $clinicA = Clinic::factory()->create();
    $clinicB = Clinic::factory()->create();
    $user = User::factory()->create();

    $speciesB = Species::withoutGlobalScopes()->create([
        'name' => 'Solo B', 'slug' => 'solo-b', 'clinic_id' => $clinicB->id,
        'is_system' => false, 'is_active' => true,
    ]);

    app()->instance('current.clinic', $clinicA);

    $this->actingAs($user)
        ->getJson(route('api.catalog.species', ['q' => 'Solo B']))
        ->assertJsonMissing(['name' => 'Solo B']);
});
