<?php

use App\Domain\Catalog\Veterinary\Models\Species;
use App\Domain\Clinic\Models\Clinic;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('scope filters to global and own clinic when current.clinic bound', function () {
    $clinic = Clinic::factory()->create();
    $other = Clinic::factory()->create();

    Species::withoutGlobalScopes()->create([
        'name' => 'Global', 'slug' => 'global', 'clinic_id' => null, 'is_system' => true, 'is_active' => true,
    ]);
    Species::withoutGlobalScopes()->create([
        'name' => 'Own', 'slug' => 'own', 'clinic_id' => $clinic->id, 'is_system' => false, 'is_active' => true,
    ]);
    Species::withoutGlobalScopes()->create([
        'name' => 'Other', 'slug' => 'other', 'clinic_id' => $other->id, 'is_system' => false, 'is_active' => true,
    ]);

    app()->instance('current.clinic', $clinic);

    $names = Species::pluck('name')->toArray();

    expect($names)->toContain('Global')
        ->toContain('Own')
        ->not->toContain('Other');
});

test('scope sees all when no current.clinic bound', function () {
    $clinic = Clinic::factory()->create();

    Species::withoutGlobalScopes()->create([
        'name' => 'Global', 'slug' => 'g', 'clinic_id' => null, 'is_system' => true, 'is_active' => true,
    ]);
    Species::withoutGlobalScopes()->create([
        'name' => 'Clinic', 'slug' => 'c', 'clinic_id' => $clinic->id, 'is_system' => false, 'is_active' => true,
    ]);

    app()->forgetInstance('current.clinic');

    $names = Species::pluck('name')->toArray();

    expect($names)->toContain('Global')->toContain('Clinic');
});
