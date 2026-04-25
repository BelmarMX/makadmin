<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    config(['branding.apex_domain' => 'makadmin.test']);
    Notification::fake();
});

test('clinic admin can create a user and send invitation', function () {
    [$clinic, $branch] = task03ClinicContext();
    $admin = task03ClinicAdmin($clinic, $branch);
    Role::findOrCreate('veterinarian', 'web');

    $this->actingAs($admin)
        ->post(task03ClinicRoute('clinic.users.store', $clinic), [
            'name' => 'Dra. Nueva',
            'email' => 'nueva@example.test',
            'phone' => '5512345678',
            'branch_id' => $branch->id,
            'professional_license' => '12345678',
            'password' => 'password-temp',
            'password_confirmation' => 'password-temp',
            'roles' => ['veterinarian'],
        ])
        ->assertRedirect();

    $user = User::withoutGlobalScopes()->where('email', 'nueva@example.test')->firstOrFail();

    expect($user->clinic_id)->toBe($clinic->id);
    expect($user->branch_id)->toBe($branch->id);
    expect($user->is_active)->toBeTrue();

    setPermissionsTeamId($clinic->id);
    expect($user->hasRole('veterinarian'))->toBeTrue();

    Notification::assertSentTo($user, ResetPassword::class);
});

test('validates required user fields', function () {
    [$clinic, $branch] = task03ClinicContext();
    $admin = task03ClinicAdmin($clinic, $branch);

    $this->actingAs($admin)
        ->post(task03ClinicRoute('clinic.users.store', $clinic), [])
        ->assertSessionHasErrors(['name', 'email', 'branch_id', 'password', 'roles']);
});
