<?php

use App\Models\User;

test('super admin can access clinic users without explicit clinic permissions', function () {
    [$clinic, $branch] = task03ClinicContext();
    User::factory()->create([
        'clinic_id' => $clinic->id,
        'branch_id' => $branch->id,
        'is_active' => true,
    ]);

    $superAdmin = User::factory()->create([
        'clinic_id' => null,
        'branch_id' => null,
        'is_super_admin' => true,
    ]);

    $this->actingAs($superAdmin)
        ->get(task03ClinicRoute('clinic.users.index', $clinic))
        ->assertSuccessful();
});
