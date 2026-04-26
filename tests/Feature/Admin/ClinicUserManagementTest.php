<?php

use App\Domain\Clinic\Models\Clinic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('superadmin can deactivate a clinic user', function () {
    $superAdmin = User::factory()->create(['is_super_admin' => true]);
    $clinic = Clinic::factory()->create();
    $clinicUser = User::factory()->create([
        'clinic_id' => $clinic->id,
        'is_active' => true,
    ]);

    $this->actingAs($superAdmin)
        ->post(route('admin.clinics.users.deactivate', [
            'clinic' => $clinic->id,
            'user' => $clinicUser->id,
        ]))
        ->assertRedirect();

    expect($clinicUser->fresh()->is_active)->toBeFalse();
});

it('superadmin can update clinic user data', function () {
    $superAdmin = User::factory()->create(['is_super_admin' => true]);
    $clinic = Clinic::factory()->create();
    $clinicUser = User::factory()->create([
        'clinic_id' => $clinic->id,
    ]);

    $this->actingAs($superAdmin)
        ->put(route('admin.clinics.users.update', [
            'clinic' => $clinic->id,
            'user' => $clinicUser->id,
        ]), [
            'name' => 'Nombre Actualizado',
            'email' => $clinicUser->email,
            'phone' => '+52 55 0000 0000',
        ])
        ->assertRedirect();

    expect($clinicUser->fresh()->name)->toBe('Nombre Actualizado');
});
