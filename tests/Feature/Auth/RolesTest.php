<?php

use App\Domain\Clinic\Models\Clinic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

test('spatie teams are scoped by clinic_id', function () {
    $clinicA = Clinic::create([
        'slug' => 'role-clinic-a',
        'legal_name' => 'Role Clinic A',
        'commercial_name' => 'Role A',
        'responsible_vet_name' => 'Dr Role A',
        'responsible_vet_license' => '777',
        'contact_phone' => '5500000007',
        'contact_email' => 'rolea@test.com',
    ]);

    $clinicB = Clinic::create([
        'slug' => 'role-clinic-b',
        'legal_name' => 'Role Clinic B',
        'commercial_name' => 'Role B',
        'responsible_vet_name' => 'Dr Role B',
        'responsible_vet_license' => '888',
        'contact_phone' => '5500000008',
        'contact_email' => 'roleb@test.com',
    ]);

    $userA = User::factory()->create(['clinic_id' => $clinicA->id]);

    $role = Role::create(['name' => 'veterinarian', 'guard_name' => 'web']);

    $registrar = app(PermissionRegistrar::class);

    $registrar->setPermissionsTeamId($clinicA->id);
    $userA->assignRole($role);
    $registrar->forgetCachedPermissions();

    $registrar->setPermissionsTeamId($clinicA->id);
    expect($userA->fresh()->hasRole('veterinarian'))->toBeTrue();

    $registrar->setPermissionsTeamId($clinicB->id);
    $registrar->forgetCachedPermissions();
    expect($userA->fresh()->hasRole('veterinarian'))->toBeFalse();
});
