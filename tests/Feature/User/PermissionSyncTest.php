<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;

test('clinic admin can sync direct permissions by active module', function () {
    [$clinic, $branch] = task03ClinicContext();
    $admin = task03ClinicAdmin($clinic, $branch);
    $user = User::factory()->create(['clinic_id' => $clinic->id, 'branch_id' => $branch->id]);
    Permission::findOrCreate('patients.delete', 'web');

    $this->actingAs($admin)
        ->patch(task03ClinicRoute('clinic.users.permissions.update', $clinic, ['user' => $user]), [
            'permissions' => [
                'patients' => ['view', 'create'],
                'inventory' => ['delete'],
            ],
        ])
        ->assertRedirect();

    setPermissionsTeamId($clinic->id);
    $user = $user->fresh();

    expect($user->hasDirectPermission('patients.view'))->toBeTrue();
    expect($user->hasDirectPermission('patients.create'))->toBeTrue();
    expect($user->getDirectPermissions()->pluck('name'))->not->toContain('inventory.delete');

    $this->actingAs($admin)
        ->patch(task03ClinicRoute('clinic.users.permissions.update', $clinic, ['user' => $user]), [
            'permissions' => ['patients' => ['view']],
        ])
        ->assertRedirect();

    expect($user->fresh()->hasDirectPermission('patients.create'))->toBeFalse();
});
