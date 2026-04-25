<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

test('clinic admin can assign and revoke roles', function () {
    [$clinic, $branch] = task03ClinicContext();
    $admin = task03ClinicAdmin($clinic, $branch);
    $user = User::factory()->create(['clinic_id' => $clinic->id, 'branch_id' => $branch->id]);
    Role::findOrCreate('cashier', 'web');

    $this->actingAs($admin)
        ->post(task03ClinicRoute('clinic.users.roles.store', $clinic, ['user' => $user]), ['role' => 'cashier'])
        ->assertRedirect();

    setPermissionsTeamId($clinic->id);
    expect($user->fresh()->hasRole('cashier'))->toBeTrue();

    $this->actingAs($admin)
        ->delete(task03ClinicRoute('clinic.users.roles.destroy', $clinic, ['user' => $user]), ['role' => 'cashier'])
        ->assertRedirect();

    expect($user->fresh()->hasRole('cashier'))->toBeFalse();
});
