<?php

use App\Models\User;

test('clinic a cannot view or edit clinic b users', function () {
    [$clinicA, $branchA] = task03ClinicContext();
    $adminA = task03ClinicAdmin($clinicA, $branchA);

    [$clinicB, $branchB] = task03ClinicContext();
    $userB = User::factory()->create(['clinic_id' => $clinicB->id, 'branch_id' => $branchB->id]);

    app()->instance('current.clinic', $clinicA);
    setPermissionsTeamId($clinicA->id);

    $this->actingAs($adminA)
        ->get(task03ClinicRoute('clinic.users.show', $clinicA, ['user' => $userB->id]))
        ->assertNotFound();

    $this->actingAs($adminA)
        ->put(task03ClinicRoute('clinic.users.update', $clinicA, ['user' => $userB->id]), [
            'name' => 'Intento',
            'email' => 'intento@example.test',
            'branch_id' => $branchA->id,
        ])
        ->assertNotFound();
});
