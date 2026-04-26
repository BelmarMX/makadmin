<?php

use App\Contracts\Integrations\MediaStorage;
use App\Domain\Clinic\Actions\UploadClinicLogoAction;
use App\Domain\Clinic\Models\Clinic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['branding.apex_domain' => 'vetfollow.test']);
});

test('action stores logo and updates clinic logo_path', function () {
    $clinic = Clinic::factory()->create(['logo_path' => null]);
    $file = UploadedFile::fake()->image('logo.jpg', 200, 200);

    $this->mock(MediaStorage::class, function (MockInterface $mock) {
        $mock->shouldReceive('putRaw')
            ->once()
            ->withArgs(fn (string $path) => str_starts_with($path, 'logos/clinics/'))
            ->andReturnUsing(fn (string $path) => $path);
        $mock->shouldReceive('delete')->never();
    });

    app(UploadClinicLogoAction::class)->handle($clinic, $file);

    expect($clinic->fresh()->logo_path)->toStartWith('logos/clinics/');
});

test('action deletes old logo before storing new one', function () {
    $clinic = Clinic::factory()->create(['logo_path' => 'logos/clinics/1/logo_old.webp']);
    $file = UploadedFile::fake()->image('new.jpg', 200, 200);

    $this->mock(MediaStorage::class, function (MockInterface $mock) {
        $mock->shouldReceive('delete')
            ->once()
            ->with('logos/clinics/1/logo_old.webp')
            ->andReturn(true);
        $mock->shouldReceive('putRaw')
            ->once()
            ->andReturnUsing(fn (string $path) => $path);
    });

    app(UploadClinicLogoAction::class)->handle($clinic, $file);
});

test('logo path contains uuid for cache busting', function () {
    $clinic = Clinic::factory()->create(['logo_path' => null]);
    $file = UploadedFile::fake()->image('logo.png', 100, 100);

    $capturedPath = null;
    $this->mock(MediaStorage::class, function (MockInterface $mock) use (&$capturedPath) {
        $mock->shouldReceive('putRaw')
            ->once()
            ->andReturnUsing(function (string $path) use (&$capturedPath) {
                $capturedPath = $path;

                return $path;
            });
    });

    app(UploadClinicLogoAction::class)->handle($clinic, $file);

    expect($capturedPath)->toMatch('/^logos\/clinics\/\d+\/logo_[a-f0-9\-]+\.webp$/');
});

test('super admin can upload logo via HTTP', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $clinic = Clinic::factory()->create(['logo_path' => null]);
    $file = UploadedFile::fake()->image('logo.jpg', 300, 300);

    $this->mock(MediaStorage::class, function (MockInterface $mock) {
        $mock->shouldReceive('putRaw')->once()->andReturnUsing(fn (string $p) => $p);
    });

    $this->actingAs($admin)
        ->post(route('admin.clinics.upload-logo', $clinic), ['image' => $file])
        ->assertRedirect();

    expect($clinic->fresh()->logo_path)->toStartWith('logos/clinics/');
});

test('upload rejects non-image file', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $clinic = Clinic::factory()->create();
    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    $this->actingAs($admin)
        ->post(route('admin.clinics.upload-logo', $clinic), ['image' => $file])
        ->assertSessionHasErrors('image');
});

test('upload rejects file over 5MB', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $clinic = Clinic::factory()->create();
    $file = UploadedFile::fake()->create('big.jpg', 6000, 'image/jpeg');

    $this->actingAs($admin)
        ->post(route('admin.clinics.upload-logo', $clinic), ['image' => $file])
        ->assertSessionHasErrors('image');
});

test('super admin can remove clinic logo', function () {
    $clinic = Clinic::factory()->create(['logo_path' => 'logos/clinics/1/logo_old.webp']);
    $admin = User::factory()->create(['is_super_admin' => true]);

    $this->mock(MediaStorage::class, function (MockInterface $mock) {
        $mock->shouldReceive('delete')->once()->with('logos/clinics/1/logo_old.webp')->andReturn(true);
    });

    $this->actingAs($admin)
        ->delete(route('admin.clinics.destroy-logo', $clinic))
        ->assertRedirect();

    expect($clinic->fresh()->logo_path)->toBeNull();
});
