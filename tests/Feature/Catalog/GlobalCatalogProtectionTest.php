<?php

use App\Domain\Catalog\Veterinary\Models\Species;
use App\Domain\Clinic\Models\Clinic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['branding.apex_domain' => 'vetfollow.test']);
});

test('clinic user cannot update a system species', function () {
    $clinic = Clinic::factory()->create();
    $user = User::factory()->create(['is_super_admin' => false]);

    $system = Species::withoutGlobalScopes()->create([
        'name' => 'Canino', 'slug' => 'canino', 'clinic_id' => null,
        'is_system' => true, 'is_active' => true,
    ]);

    app()->instance('current.clinic', $clinic);

    $this->actingAs($user)
        ->put(route('admin.catalog.update', $system->id), [
            'type' => 'species',
            'name' => 'Canino Modificado',
        ])
        ->assertForbidden();
});

test('clinic user cannot archive a system species', function () {
    $clinic = Clinic::factory()->create();
    $user = User::factory()->create(['is_super_admin' => false]);

    $system = Species::withoutGlobalScopes()->create([
        'name' => 'Felino', 'slug' => 'felino', 'clinic_id' => null,
        'is_system' => true, 'is_active' => true,
    ]);

    $this->actingAs($user)
        ->delete(route('admin.catalog.destroy', $system->id), [
            'type' => 'species',
        ])
        ->assertForbidden();
});

test('super admin can update a system species', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);

    $system = Species::withoutGlobalScopes()->create([
        'name' => 'Reptil', 'slug' => 'reptil', 'clinic_id' => null,
        'is_system' => true, 'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->put(route('admin.catalog.update', $system->id), [
            'type' => 'species',
            'name' => 'Reptil Actualizado',
        ])
        ->assertRedirect();

    expect($system->fresh()->name)->toBe('Reptil Actualizado');
});
