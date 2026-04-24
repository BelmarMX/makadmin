<?php

use App\Domain\Clinic\Models\Clinic;
use App\Domain\Clinic\Models\ClinicBranch;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['branding.apex_domain' => 'vetfollow.test']);
    Notification::fake();
});

test('super admin can invite a clinic admin', function () {
    $clinic = Clinic::factory()->create();
    $branch = ClinicBranch::withoutGlobalScopes()->create([
        'clinic_id' => $clinic->id,
        'name' => 'Principal',
        'address' => 'Av. Test 1',
        'is_main' => true,
    ]);
    $admin = User::factory()->create(['is_super_admin' => true]);

    $this->actingAs($admin)
        ->post(route('admin.clinics.invite-admin', $clinic), [
            'name' => 'Nuevo Admin',
            'email' => 'nuevoadmin@clinic.com',
        ])
        ->assertRedirect();

    expect(User::where('email', 'nuevoadmin@clinic.com')->exists())->toBeTrue();
});

test('invitation sends password reset notification', function () {
    $clinic = Clinic::factory()->create();
    ClinicBranch::withoutGlobalScopes()->create([
        'clinic_id' => $clinic->id,
        'name' => 'Principal',
        'address' => 'Av. Test 1',
        'is_main' => true,
    ]);
    $admin = User::factory()->create(['is_super_admin' => true]);

    $this->actingAs($admin)
        ->post(route('admin.clinics.invite-admin', $clinic), [
            'name' => 'Admin Inv',
            'email' => 'admininv@clinic.com',
        ]);

    Notification::assertSentTo(
        User::where('email', 'admininv@clinic.com')->first(),
        ResetPassword::class,
    );
});

test('invited admin gets clinic_admin role', function () {
    Role::create(['name' => 'clinic_admin', 'guard_name' => 'web']);

    $clinic = Clinic::factory()->create();
    ClinicBranch::withoutGlobalScopes()->create([
        'clinic_id' => $clinic->id,
        'name' => 'Principal',
        'address' => 'Av. Test 1',
        'is_main' => true,
    ]);
    $admin = User::factory()->create(['is_super_admin' => true]);

    $this->actingAs($admin)
        ->post(route('admin.clinics.invite-admin', $clinic), [
            'name' => 'Admin Role',
            'email' => 'adminrole@clinic.com',
        ]);

    $invitedUser = User::where('email', 'adminrole@clinic.com')->first();
    setPermissionsTeamId($clinic->id);

    expect($invitedUser->fresh()->hasRole('clinic_admin'))->toBeTrue();
});

test('non-super-admin cannot invite clinic admin', function () {
    $clinic = Clinic::factory()->create();
    $user = User::factory()->create(['is_super_admin' => false]);

    $this->actingAs($user)
        ->post(route('admin.clinics.invite-admin', $clinic), [
            'name' => 'Hacker',
            'email' => 'hacker@evil.com',
        ])
        ->assertForbidden();
});
