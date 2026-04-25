<?php

use App\Models\User;

test('clinic admin can deactivate and restore user without physical deletion', function () {
    [$clinic, $branch] = task03ClinicContext();
    $admin = task03ClinicAdmin($clinic, $branch);
    $user = User::factory()->create(['clinic_id' => $clinic->id, 'branch_id' => $branch->id, 'is_active' => true]);

    $this->actingAs($admin)
        ->post(task03ClinicRoute('clinic.users.deactivate', $clinic, ['user' => $user]))
        ->assertRedirect();

    expect($user->fresh()->is_active)->toBeFalse();
    expect(User::withoutGlobalScopes()->find($user->id))->not->toBeNull();

    $this->actingAs($admin)
        ->post(task03ClinicRoute('clinic.users.restore', $clinic, ['user' => $user]))
        ->assertRedirect();

    expect($user->fresh()->is_active)->toBeTrue();
});
