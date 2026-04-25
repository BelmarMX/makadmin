<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('user can update own clinic profile', function () {
    [$clinic, $branch] = task03ClinicContext();
    $user = task03ClinicAdmin($clinic, $branch);
    Storage::fake('public');

    $this->actingAs($user)
        ->put(task03ClinicRoute('clinic.profile.update', $clinic), [
            'name' => 'Perfil Actualizado',
            'phone' => '5500001111',
            'password' => 'password-nuevo',
            'password_confirmation' => 'password-nuevo',
            'avatar' => UploadedFile::fake()->image('perfil.png'),
        ])
        ->assertRedirect();

    $user = $user->fresh();

    expect($user->name)->toBe('Perfil Actualizado');
    expect($user->phone)->toBe('5500001111');
    expect($user->avatar_path)->not->toBeNull();
});
