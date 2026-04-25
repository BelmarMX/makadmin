<?php

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
