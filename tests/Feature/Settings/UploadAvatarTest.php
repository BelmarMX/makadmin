<?php

use App\Actions\UploadUserAvatarAction;
use App\Contracts\Integrations\MediaStorage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

test('action stores avatar and updates user avatar_path', function () {
    $user = User::factory()->create(['avatar_path' => null]);
    $file = UploadedFile::fake()->image('avatar.jpg', 200, 200);

    $this->mock(MediaStorage::class, function (MockInterface $mock) {
        $mock->shouldReceive('putRaw')
            ->once()
            ->withArgs(fn (string $path) => str_starts_with($path, 'avatars/users/'))
            ->andReturnUsing(fn (string $path) => $path);
        $mock->shouldReceive('delete')->never();
    });

    app(UploadUserAvatarAction::class)->handle($user, $file);

    expect($user->fresh()->avatar_path)->toStartWith('avatars/users/');
});

test('action deletes old avatar before storing new one', function () {
    $user = User::factory()->create(['avatar_path' => 'avatars/users/1/avatar_old.webp']);
    $file = UploadedFile::fake()->image('new.jpg', 200, 200);

    $this->mock(MediaStorage::class, function (MockInterface $mock) {
        $mock->shouldReceive('delete')
            ->once()
            ->with('avatars/users/1/avatar_old.webp')
            ->andReturn(true);
        $mock->shouldReceive('putRaw')->once()->andReturnUsing(fn (string $p) => $p);
    });

    app(UploadUserAvatarAction::class)->handle($user, $file);
});

test('avatar path contains uuid for cache busting', function () {
    $user = User::factory()->create(['avatar_path' => null]);
    $file = UploadedFile::fake()->image('photo.png', 100, 100);

    $capturedPath = null;
    $this->mock(MediaStorage::class, function (MockInterface $mock) use (&$capturedPath) {
        $mock->shouldReceive('putRaw')->once()
            ->andReturnUsing(function (string $path) use (&$capturedPath) {
                $capturedPath = $path;

                return $path;
            });
    });

    app(UploadUserAvatarAction::class)->handle($user, $file);

    expect($capturedPath)->toMatch('/^avatars\/users\/\d+\/avatar_[a-f0-9\-]+\.webp$/');
});

test('authenticated user can upload avatar via HTTP', function () {
    $user = User::factory()->create(['avatar_path' => null]);
    $file = UploadedFile::fake()->image('me.jpg', 200, 200);

    $this->mock(MediaStorage::class, function (MockInterface $mock) {
        $mock->shouldReceive('putRaw')->once()->andReturnUsing(fn (string $p) => $p);
    });

    $this->actingAs($user)
        ->post(route('settings.profile.avatar.store'), ['image' => $file])
        ->assertRedirect();

    expect($user->fresh()->avatar_path)->toStartWith('avatars/users/');
});

test('avatar upload rejects non-image file', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('settings.profile.avatar.store'), [
            'image' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
        ])
        ->assertSessionHasErrors('image');
});

test('authenticated user can remove avatar', function () {
    $user = User::factory()->create(['avatar_path' => 'avatars/users/1/avatar_old.webp']);

    $this->mock(MediaStorage::class, function (MockInterface $mock) {
        $mock->shouldReceive('delete')->once()->with('avatars/users/1/avatar_old.webp')->andReturn(true);
    });

    $this->actingAs($user)
        ->delete(route('settings.profile.avatar.destroy'))
        ->assertRedirect();

    expect($user->fresh()->avatar_path)->toBeNull();
});
