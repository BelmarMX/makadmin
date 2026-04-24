<?php

use App\Domain\Clinic\Models\Clinic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['branding.apex_domain' => 'vetfollow.test']);
});

test('super admin can update general clinic data', function () {
    $clinic = Clinic::factory()->create();
    $admin = User::factory()->create(['is_super_admin' => true]);

    $this->actingAs($admin)
        ->put(route('admin.clinics.update', $clinic), [
            'slug' => $clinic->slug,
            'commercial_name' => 'Updated Name',
            'legal_name' => $clinic->legal_name,
            'contact_email' => $clinic->contact_email,
            'contact_phone' => $clinic->contact_phone,
            'responsible_vet_name' => $clinic->responsible_vet_name,
            'responsible_vet_license' => $clinic->responsible_vet_license,
        ])
        ->assertRedirect();

    expect($clinic->fresh()->commercial_name)->toBe('Updated Name');
});

test('non-super-admin cannot update clinic', function () {
    $clinic = Clinic::factory()->create();
    $user = User::factory()->create(['is_super_admin' => false]);

    $this->actingAs($user)
        ->put(route('admin.clinics.update', $clinic), [
            'commercial_name' => 'Hack',
        ])
        ->assertForbidden();
});

test('slug change is rejected if it conflicts with existing clinic', function () {
    $clinicA = Clinic::factory()->create(['slug' => 'clinic-a']);
    Clinic::factory()->create(['slug' => 'clinic-b']);
    $admin = User::factory()->create(['is_super_admin' => true]);

    $this->actingAs($admin)
        ->put(route('admin.clinics.update', $clinicA), [
            'slug' => 'clinic-b',
            'commercial_name' => $clinicA->commercial_name,
            'legal_name' => $clinicA->legal_name,
            'contact_email' => $clinicA->contact_email,
            'contact_phone' => $clinicA->contact_phone,
            'responsible_vet_name' => $clinicA->responsible_vet_name,
            'responsible_vet_license' => $clinicA->responsible_vet_license,
        ])
        ->assertSessionHasErrors('slug');
});

test('updating clinic with same slug succeeds (unique ignores self)', function () {
    $clinic = Clinic::factory()->create(['slug' => 'same-slug']);
    $admin = User::factory()->create(['is_super_admin' => true]);

    $this->actingAs($admin)
        ->put(route('admin.clinics.update', $clinic), [
            'slug' => 'same-slug',
            'commercial_name' => 'New Name',
            'legal_name' => $clinic->legal_name,
            'contact_email' => $clinic->contact_email,
            'contact_phone' => $clinic->contact_phone,
            'responsible_vet_name' => $clinic->responsible_vet_name,
            'responsible_vet_license' => $clinic->responsible_vet_license,
        ])
        ->assertRedirect();

    expect($clinic->fresh()->commercial_name)->toBe('New Name');
});
