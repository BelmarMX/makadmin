<?php

use App\Domain\User\Models\UserBranchPermission;
use App\Models\User;

it('syncs branch permissions for a user', function () {
    [$clinic, $branch] = task03ClinicContext();
    $admin = task03ClinicAdmin($clinic, $branch);
    $target = User::factory()->create([
        'clinic_id' => $clinic->id,
        'branch_id' => $branch->id,
    ]);

    $this->actingAs($admin)
        ->patch(task03ClinicRoute('clinic.users.permissions.update', $clinic, ['user' => $target]), [
            'branch_id' => $branch->id,
            'permissions' => ['patients' => ['view', 'create']],
        ])
        ->assertRedirect();

    expect(UserBranchPermission::where('user_id', $target->id)
        ->where('branch_id', $branch->id)
        ->count())->toBe(2);
});
