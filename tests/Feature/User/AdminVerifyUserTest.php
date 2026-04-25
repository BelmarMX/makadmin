<?php

use App\Models\User;

test('super admin verification activates the clinic user', function () {
    [$clinic, $branch] = task03ClinicContext();
    $superAdmin = User::factory()->create(['is_super_admin' => true]);
    $user = User::factory()->create([
        'clinic_id' => $clinic->id,
        'branch_id' => $branch->id,
        'email_verified_at' => null,
        'is_active' => false,
    ]);

    $this->actingAs($superAdmin)
        ->post(route('admin.clinics.users.verify-email', ['clinic' => $clinic, 'user' => $user]))
        ->assertRedirect();

    $user->refresh();

    expect($user->email_verified_at)->not->toBeNull();
    expect($user->is_active)->toBeTrue();
});
