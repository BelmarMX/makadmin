<?php

use App\Domain\Catalog\Veterinary\Models\Species;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['branding.apex_domain' => 'vetfollow.test']);
});

test('super admin can create a base species', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);

    $this->actingAs($admin)
        ->post(route('admin.catalog.store'), [
            'type' => 'species',
            'name' => 'Ornitorrinco',
            'icon' => 'paw-print',
            'sort_order' => 99,
        ])
        ->assertRedirect();

    $species = Species::withoutGlobalScopes()->where('name', 'Ornitorrinco')->first();
    expect($species)->not->toBeNull()
        ->and($species->is_system)->toBeTrue()
        ->and($species->clinic_id)->toBeNull();
});

test('super admin can update a species', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);

    $species = Species::withoutGlobalScopes()->create([
        'name' => 'TestEspecie', 'slug' => 'test-especie',
        'clinic_id' => null, 'is_system' => true, 'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->put(route('admin.catalog.update', $species->id), [
            'type' => 'species',
            'name' => 'TestEspecie Editado',
            'sort_order' => 50,
        ])
        ->assertRedirect();

    expect($species->fresh()->name)->toBe('TestEspecie Editado');
});

test('super admin can archive a species', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);

    $species = Species::withoutGlobalScopes()->create([
        'name' => 'Para Archivar', 'slug' => 'para-archivar',
        'clinic_id' => null, 'is_system' => true, 'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.catalog.destroy', $species->id), [
            'type' => 'species',
        ])
        ->assertRedirect();

    expect(Species::withoutGlobalScopes()->withTrashed()->find($species->id)?->deleted_at)->not->toBeNull();
});

test('api species endpoint returns results', function () {
    $user = User::factory()->create();

    Species::withoutGlobalScopes()->create([
        'name' => 'ApiCanino', 'slug' => 'api-canino',
        'clinic_id' => null, 'is_system' => true, 'is_active' => true,
    ]);

    $this->actingAs($user)
        ->getJson(route('api.catalog.species'))
        ->assertOk()
        ->assertJsonFragment(['name' => 'ApiCanino']);
});

test('api species search filters by name', function () {
    $user = User::factory()->create();

    Species::withoutGlobalScopes()->create([
        'name' => 'Gecko', 'slug' => 'gecko',
        'clinic_id' => null, 'is_system' => true, 'is_active' => true,
    ]);
    Species::withoutGlobalScopes()->create([
        'name' => 'Iguana', 'slug' => 'iguana',
        'clinic_id' => null, 'is_system' => true, 'is_active' => true,
    ]);

    $this->actingAs($user)
        ->getJson(route('api.catalog.species', ['q' => 'Gecko']))
        ->assertOk()
        ->assertJsonFragment(['name' => 'Gecko'])
        ->assertJsonMissing(['name' => 'Iguana']);
});
