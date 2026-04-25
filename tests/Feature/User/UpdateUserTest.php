<?php

use App\Domain\Clinic\Models\ClinicBranch;
use App\Domain\User\Models\UserBranchRole;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

test('clinic admin can update user data and avatar', function () {
    [$clinic, $branch] = task03ClinicContext();
    $admin = task03ClinicAdmin($clinic, $branch);
    $user = User::factory()->create(['clinic_id' => $clinic->id, 'branch_id' => $branch->id]);
    Role::findOrCreate('groomer', 'web');
    Storage::fake('public');

    $this->actingAs($admin)
        ->put(task03ClinicRoute('clinic.users.update', $clinic, ['user' => $user]), [
            'name' => 'Nombre Actualizado',
            'email' => 'actualizado@example.test',
            'phone' => '5599999999',
            'branch_id' => $branch->id,
            'professional_license' => '87654321',
            'password' => '',
            'password_confirmation' => '',
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
            'roles' => ['groomer'],
        ])
        ->assertRedirect();

    $user = $user->fresh();

    expect($user->name)->toBe('Nombre Actualizado');
    expect($user->email)->toBe('actualizado@example.test');
    expect($user->avatar_path)->not->toBeNull();

    setPermissionsTeamId($clinic->id);
    expect($user->hasRole('groomer'))->toBeTrue();
});

test('clinic admin can assign different roles per branch', function () {
    [$clinic, $branch] = task03ClinicContext();
    $secondBranch = ClinicBranch::withoutGlobalScopes()->create([
        'clinic_id' => $clinic->id,
        'name' => 'Sucursal Norte',
        'address' => 'Av. Norte 456',
        'is_main' => false,
        'is_active' => true,
    ]);
    $admin = task03ClinicAdmin($clinic, $branch);
    $user = User::factory()->create(['clinic_id' => $clinic->id, 'branch_id' => $branch->id]);

    Role::findOrCreate('veterinarian', 'web');
    Role::findOrCreate('cashier', 'web');

    $this->actingAs($admin)
        ->put(task03ClinicRoute('clinic.users.update', $clinic, ['user' => $user]), [
            'name' => $user->name,
            'email' => $user->email,
            'branch_id' => $branch->id,
            'password' => '',
            'password_confirmation' => '',
            'roles' => ['veterinarian', 'cashier'],
            'branch_roles' => [
                ['branch_id' => $branch->id, 'roles' => ['veterinarian']],
                ['branch_id' => $secondBranch->id, 'roles' => ['cashier']],
            ],
        ])
        ->assertRedirect();

    expect(UserBranchRole::withoutGlobalScopes()->where('user_id', $user->id)->pluck('role')->all())
        ->toContain('veterinarian', 'cashier');

    setPermissionsTeamId($clinic->id);
    expect($user->fresh()->hasRole('veterinarian'))->toBeTrue();
    expect($user->fresh()->hasRole('cashier'))->toBeTrue();
});
