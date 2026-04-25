# Image Uploads — Clinic Logo + User Avatar — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Allow super admins to upload a circular clinic logo (in create wizard + edit page) and users to upload a circular avatar (in Settings > Profile), with WebP conversion, cache-busting filenames, and circular display throughout including the sidebar user menu.

**Architecture:** `vue-advanced-cropper` handles circular crop in a modal on the frontend; the cropped Blob is sent as a File via Inertia multipart POST; a Laravel Action processes it with Intervention Image (cover 400×400 → WebP 80%), generates a UUID filename, deletes the old file, and persists via `MediaStorage::putRaw()`. Model accessors expose resolved URLs to the frontend.

**Tech Stack:** PHP `intervention/image` v3 (GD driver), JS `vue-advanced-cropper`, Laravel `MediaStorage` contract + `LocalMediaStorage`, Inertia `useForm` multipart, shadcn-vue `Dialog`, lucide-vue-next icons.

---

## File Map

| File | Action | Responsibility |
|------|--------|----------------|
| `app/Contracts/Integrations/MediaStorage.php` | Modify | Add `putRaw(string $path, string $contents): string` |
| `app/Integrations/Storage/Local/LocalMediaStorage.php` | Modify | Implement `putRaw` |
| `app/Domain/Clinic/Actions/UploadClinicLogoAction.php` | Create | Delete old → convert → putRaw → save path |
| `app/Actions/UploadUserAvatarAction.php` | Create | Same for User |
| `app/Http/Requests/UploadImageRequest.php` | Create | Shared image validation |
| `app/Http/Controllers/Admin/ClinicController.php` | Modify | Add `uploadLogo()`, `destroyLogo()` |
| `app/Http/Controllers/Settings/ProfileController.php` | Modify | Add `uploadAvatar()`, `destroyAvatar()` |
| `app/Domain/Clinic/Actions/CreateClinicAction.php` | Modify | Call `UploadClinicLogoAction` when logo present |
| `app/Domain/Clinic/Models/Clinic.php` | Modify | Add `logoUrl` accessor + `$appends` |
| `app/Models/User.php` | Modify | Add `avatar` accessor + `$appends` |
| `app/Http/Middleware/HandleInertiaRequests.php` | Modify | Include `avatar` in shared user props |
| `routes/admin.php` | Modify | Add logo upload/destroy routes |
| `routes/web.php` | Modify | Add avatar upload/destroy routes |
| `resources/js/components/ImageUploadCircle.vue` | Create | Dropzone + click circle, emits File |
| `resources/js/components/CropModal.vue` | Create | Dialog with CircleStencil cropper |
| `resources/js/components/domain/Clinic/ClinicWizard/StepIdentity.vue` | Modify | Add logo upload |
| `resources/js/pages/Admin/Clinics/Create.vue` | Modify | Wire logo Blob → form.logo |
| `resources/js/pages/Admin/Clinics/Edit.vue` | Modify | Logo upload with immediate POST |
| `resources/js/pages/Admin/Clinics/Show.vue` | Modify | Show logo_url circular |
| `resources/js/pages/Settings/Profile.vue` | Modify | Replace avatar section with ImageUploadCircle |
| `resources/js/types/index.ts` | Modify | Add `logo_url` to Clinic type, `avatar` to User type |
| `tests/Feature/Admin/Clinic/UploadClinicLogoTest.php` | Create | Backend logo upload tests |
| `tests/Feature/Settings/UploadAvatarTest.php` | Create | Backend avatar upload tests |

---

## Task 1: Install Packages

**Files:**
- Modify: `composer.json` (via composer)
- Modify: `package.json` (via npm)

- [ ] **Step 1: Install intervention/image v3**

```bash
composer require intervention/image:"^3.0"
```

Expected: `intervention/image 3.x` in `composer.lock`.

- [ ] **Step 2: Install vue-advanced-cropper**

```bash
npm install vue-advanced-cropper
```

Expected: `"vue-advanced-cropper"` in `package.json` dependencies.

- [ ] **Step 3: Commit**

```bash
git add composer.json composer.lock package.json package-lock.json
git commit -m "chore: install intervention/image v3 and vue-advanced-cropper"
```

---

## Task 2: Extend MediaStorage Contract + LocalMediaStorage

**Files:**
- Modify: `app/Contracts/Integrations/MediaStorage.php`
- Modify: `app/Integrations/Storage/Local/LocalMediaStorage.php`

- [ ] **Step 1: Add `putRaw` to interface**

Edit `app/Contracts/Integrations/MediaStorage.php` — add after `put()`:

```php
public function putRaw(string $path, string $contents): string;
```

Full file after edit:
```php
<?php

namespace App\Contracts\Integrations;

use Illuminate\Http\UploadedFile;

interface MediaStorage
{
    public function put(string $path, UploadedFile $file): string;

    public function putRaw(string $path, string $contents): string;

    public function url(string $path): string;

    public function delete(string $path): bool;
}
```

- [ ] **Step 2: Implement `putRaw` in LocalMediaStorage**

Edit `app/Integrations/Storage/Local/LocalMediaStorage.php` — add method:

```php
public function putRaw(string $path, string $contents): string
{
    Storage::disk('public')->put($path, $contents);

    return $path;
}
```

- [ ] **Step 3: Run PHPStan to verify no interface errors**

```bash
vendor/bin/phpstan analyse app/Contracts app/Integrations --memory-limit=1G --level=6 2>&1 | grep -v Xdebug
```

Expected: `[OK] No errors`

- [ ] **Step 4: Commit**

```bash
git add app/Contracts/Integrations/MediaStorage.php app/Integrations/Storage/Local/LocalMediaStorage.php
git commit -m "feat: add putRaw to MediaStorage contract and LocalMediaStorage"
```

---

## Task 3: Create UploadClinicLogoAction (TDD)

**Files:**
- Create: `app/Domain/Clinic/Actions/UploadClinicLogoAction.php`
- Create: `tests/Feature/Admin/Clinic/UploadClinicLogoTest.php`

- [ ] **Step 1: Write failing tests**

Create `tests/Feature/Admin/Clinic/UploadClinicLogoTest.php`:

```php
<?php

use App\Contracts\Integrations\MediaStorage;
use App\Domain\Clinic\Actions\UploadClinicLogoAction;
use App\Domain\Clinic\Models\Clinic;
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
            ->withArgs(fn (string $path) => str_starts_with($path, "logos/clinics/"))
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

    // path format: logos/clinics/{id}/logo_{uuid}.webp
    expect($capturedPath)->toMatch('/^logos\/clinics\/\d+\/logo_[a-f0-9\-]+\.webp$/');
});

test('super admin can upload logo via HTTP', function () {
    $admin = \App\Models\User::factory()->create(['is_super_admin' => true]);
    $clinic = Clinic::factory()->create(['logo_path' => null]);
    $file = UploadedFile::fake()->image('logo.jpg', 300, 300);

    $this->mock(MediaStorage::class, function (MockInterface $mock) {
        $mock->shouldReceive('putRaw')->once()->andReturn('logos/clinics/1/logo_abc.webp');
    });

    $this->actingAs($admin)
        ->post(route('admin.clinics.upload-logo', $clinic), ['image' => $file])
        ->assertRedirect();

    expect($clinic->fresh()->logo_path)->toBe('logos/clinics/1/logo_abc.webp');
});

test('upload rejects non-image file', function () {
    $admin = \App\Models\User::factory()->create(['is_super_admin' => true]);
    $clinic = Clinic::factory()->create();
    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    $this->actingAs($admin)
        ->post(route('admin.clinics.upload-logo', $clinic), ['image' => $file])
        ->assertSessionHasErrors('image');
});

test('upload rejects file over 5MB', function () {
    $admin = \App\Models\User::factory()->create(['is_super_admin' => true]);
    $clinic = Clinic::factory()->create();
    $file = UploadedFile::fake()->create('big.jpg', 6000, 'image/jpeg');

    $this->actingAs($admin)
        ->post(route('admin.clinics.upload-logo', $clinic), ['image' => $file])
        ->assertSessionHasErrors('image');
});

test('super admin can remove clinic logo', function () {
    $clinic = Clinic::factory()->create(['logo_path' => 'logos/clinics/1/logo_old.webp']);
    $admin = \App\Models\User::factory()->create(['is_super_admin' => true]);

    $this->mock(MediaStorage::class, function (MockInterface $mock) {
        $mock->shouldReceive('delete')->once()->with('logos/clinics/1/logo_old.webp')->andReturn(true);
    });

    $this->actingAs($admin)
        ->delete(route('admin.clinics.destroy-logo', $clinic))
        ->assertRedirect();

    expect($clinic->fresh()->logo_path)->toBeNull();
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
php artisan test --compact tests/Feature/Admin/Clinic/UploadClinicLogoTest.php 2>&1 | grep -v Xdebug | tail -5
```

Expected: multiple FAILs (`UploadClinicLogoAction not found`, `route not found`).

- [ ] **Step 3: Create the Action**

Create `app/Domain/Clinic/Actions/UploadClinicLogoAction.php`:

```php
<?php

namespace App\Domain\Clinic\Actions;

use App\Contracts\Integrations\MediaStorage;
use App\Domain\Clinic\Models\Clinic;
use Illuminate\Http\UploadedFile;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Illuminate\Support\Str;

class UploadClinicLogoAction
{
    public function __construct(private readonly MediaStorage $media) {}

    public function handle(Clinic $clinic, UploadedFile $file): void
    {
        if ($clinic->logo_path) {
            $this->media->delete($clinic->logo_path);
        }

        $path = "logos/clinics/{$clinic->id}/logo_" . Str::uuid() . '.webp';

        $manager = new ImageManager(new Driver());
        $webp = (string) $manager->read($file->getRealPath())->cover(400, 400)->toWebp(80);

        $this->media->putRaw($path, $webp);

        $clinic->logo_path = $path;
        $clinic->save();
    }
}
```

- [ ] **Step 4: Create UploadImageRequest**

Create `app/Http/Requests/UploadImageRequest.php`:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
        ];
    }
}
```

- [ ] **Step 5: Add routes**

In `routes/admin.php`, inside the `clinics` group (after existing routes):

```php
Route::post('/{clinic}/logo', [ClinicController::class, 'uploadLogo'])->name('upload-logo');
Route::delete('/{clinic}/logo', [ClinicController::class, 'destroyLogo'])->name('destroy-logo');
```

- [ ] **Step 6: Add controller methods**

In `app/Http/Controllers/Admin/ClinicController.php`, add imports and two methods:

Add imports:
```php
use App\Domain\Clinic\Actions\UploadClinicLogoAction;
use App\Http\Requests\UploadImageRequest;
```

Add methods:
```php
public function uploadLogo(UploadImageRequest $request, Clinic $clinic, UploadClinicLogoAction $action): RedirectResponse
{
    $this->authorize('update', $clinic);
    $action->handle($clinic, $request->file('image'));

    return back()->with('success', 'Logo actualizado.');
}

public function destroyLogo(Clinic $clinic): RedirectResponse
{
    $this->authorize('update', $clinic);

    if ($clinic->logo_path) {
        app(MediaStorage::class)->delete($clinic->logo_path);
        $clinic->logo_path = null;
        $clinic->save();
    }

    return back()->with('success', 'Logo eliminado.');
}
```

Also add to imports:
```php
use App\Contracts\Integrations\MediaStorage;
```

- [ ] **Step 7: Run tests — expect pass**

```bash
php artisan test --compact tests/Feature/Admin/Clinic/UploadClinicLogoTest.php 2>&1 | grep -v Xdebug | tail -5
```

Expected: 6 passed.

- [ ] **Step 8: Regenerate Wayfinder**

```bash
php artisan wayfinder:generate 2>&1 | grep -v Xdebug | tail -3
```

- [ ] **Step 9: Commit**

```bash
git add app/Domain/Clinic/Actions/UploadClinicLogoAction.php app/Http/Requests/UploadImageRequest.php app/Http/Controllers/Admin/ClinicController.php routes/admin.php tests/Feature/Admin/Clinic/UploadClinicLogoTest.php resources/js/actions/
git commit -m "feat: add clinic logo upload/remove backend"
```

---

## Task 4: Create UploadUserAvatarAction (TDD)

**Files:**
- Create: `app/Actions/UploadUserAvatarAction.php`
- Create: `tests/Feature/Settings/UploadAvatarTest.php`

- [ ] **Step 1: Write failing tests**

Create `tests/Feature/Settings/UploadAvatarTest.php`:

```php
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
        $mock->shouldReceive('putRaw')->once()->andReturn('avatars/users/1/avatar_abc.webp');
    });

    $this->actingAs($user)
        ->post(route('settings.profile.avatar.store'), ['image' => $file])
        ->assertRedirect();

    expect($user->fresh()->avatar_path)->toBe('avatars/users/1/avatar_abc.webp');
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
```

- [ ] **Step 2: Run to verify fail**

```bash
php artisan test --compact tests/Feature/Settings/UploadAvatarTest.php 2>&1 | grep -v Xdebug | tail -5
```

Expected: FAILs (`UploadUserAvatarAction not found`, routes missing).

- [ ] **Step 3: Create the Action**

Create `app/Actions/UploadUserAvatarAction.php`:

```php
<?php

namespace App\Actions;

use App\Contracts\Integrations\MediaStorage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class UploadUserAvatarAction
{
    public function __construct(private readonly MediaStorage $media) {}

    public function handle(User $user, UploadedFile $file): void
    {
        if ($user->avatar_path) {
            $this->media->delete($user->avatar_path);
        }

        $path = "avatars/users/{$user->id}/avatar_" . Str::uuid() . '.webp';

        $manager = new ImageManager(new Driver());
        $webp = (string) $manager->read($file->getRealPath())->cover(400, 400)->toWebp(80);

        $this->media->putRaw($path, $webp);

        $user->avatar_path = $path;
        $user->save();
    }
}
```

- [ ] **Step 4: Add avatar routes in web.php**

In `routes/web.php`, inside the settings group (near other profile routes):

```php
Route::post('/settings/profile/avatar', [\App\Http\Controllers\Settings\ProfileController::class, 'uploadAvatar'])->name('settings.profile.avatar.store');
Route::delete('/settings/profile/avatar', [\App\Http\Controllers\Settings\ProfileController::class, 'destroyAvatar'])->name('settings.profile.avatar.destroy');
```

- [ ] **Step 5: Add controller methods to ProfileController**

In `app/Http/Controllers/Settings/ProfileController.php`, add imports:
```php
use App\Actions\UploadUserAvatarAction;
use App\Contracts\Integrations\MediaStorage;
use App\Http\Requests\UploadImageRequest;
```

Add methods:
```php
public function uploadAvatar(UploadImageRequest $request, UploadUserAvatarAction $action): RedirectResponse
{
    $action->handle($request->user(), $request->file('image'));

    return back()->with('status', 'avatar-updated');
}

public function destroyAvatar(Request $request): RedirectResponse
{
    $user = $request->user();

    if ($user->avatar_path) {
        app(MediaStorage::class)->delete($user->avatar_path);
        $user->avatar_path = null;
        $user->save();
    }

    return back()->with('status', 'avatar-removed');
}
```

- [ ] **Step 6: Run tests — expect pass**

```bash
php artisan test --compact tests/Feature/Settings/UploadAvatarTest.php 2>&1 | grep -v Xdebug | tail -5
```

Expected: 6 passed.

- [ ] **Step 7: Regenerate Wayfinder**

```bash
php artisan wayfinder:generate 2>&1 | grep -v Xdebug | tail -3
```

- [ ] **Step 8: Commit**

```bash
git add app/Actions/UploadUserAvatarAction.php app/Http/Controllers/Settings/ProfileController.php routes/web.php tests/Feature/Settings/UploadAvatarTest.php resources/js/actions/ resources/js/routes/
git commit -m "feat: add user avatar upload/remove backend"
```

---

## Task 5: Model Accessors + Shared Props

**Files:**
- Modify: `app/Domain/Clinic/Models/Clinic.php`
- Modify: `app/Models/User.php`
- Modify: `app/Http/Middleware/HandleInertiaRequests.php`
- Modify: `resources/js/types/index.ts`

- [ ] **Step 1: Add `logoUrl` accessor to Clinic**

In `app/Domain/Clinic/Models/Clinic.php`:

Add import:
```php
use App\Contracts\Integrations\MediaStorage;
```

Add to `$appends` (add the property):
```php
protected $appends = ['logo_url'];
```

Add accessor method (after existing `subdomainUrl` accessor):
```php
protected function logoUrl(): Attribute
{
    return Attribute::make(
        get: fn () => $this->logo_path
            ? app(MediaStorage::class)->url($this->logo_path)
            : null,
    );
}
```

- [ ] **Step 2: Add `avatar` accessor to User**

In `app/Models/User.php`:

Add import:
```php
use App\Contracts\Integrations\MediaStorage;
use Illuminate\Database\Eloquent\Casts\Attribute;
```

Add to `$appends`:
```php
protected $appends = ['avatar'];
```

Add accessor (after `casts()` method):
```php
protected function avatar(): Attribute
{
    return Attribute::make(
        get: fn () => $this->avatar_path
            ? app(MediaStorage::class)->url($this->avatar_path)
            : null,
    );
}
```

- [ ] **Step 3: Verify accessor via tinker**

```bash
php artisan tinker --execute '
$u = App\Models\User::first();
echo json_encode(["avatar_path" => $u->avatar_path, "avatar" => $u->avatar]);
' 2>&1 | grep -v Xdebug | tail -2
```

Expected: JSON with `"avatar": null` (or a URL if avatar_path is set).

- [ ] **Step 4: Update TypeScript types**

In `resources/js/types/index.ts`, find the `User` type and add `avatar`:
```ts
avatar?: string | null;
```

Find the `Clinic` type (or add if missing) and add `logo_url`:
```ts
logo_url?: string | null;
```

- [ ] **Step 5: Run PHPStan**

```bash
vendor/bin/phpstan analyse app/Domain/Clinic/Models/Clinic.php app/Models/User.php --memory-limit=1G --level=6 2>&1 | grep -v Xdebug | tail -5
```

Expected: `[OK] No errors`

- [ ] **Step 6: Commit**

```bash
git add app/Domain/Clinic/Models/Clinic.php app/Models/User.php resources/js/types/index.ts
git commit -m "feat: add logoUrl accessor to Clinic and avatar accessor to User"
```

---

## Task 6: ImageUploadCircle Component

**Files:**
- Create: `resources/js/components/ImageUploadCircle.vue`

- [ ] **Step 1: Create the component**

Create `resources/js/components/ImageUploadCircle.vue`:

```vue
<script setup lang="ts">
import { ref } from 'vue';
import { Camera, ImageIcon, X } from 'lucide-vue-next';

const props = withDefaults(defineProps<{
    modelValue?: string | null;
    size?: 'sm' | 'md' | 'lg';
    disabled?: boolean;
    label?: string;
    error?: string;
}>(), {
    modelValue: null,
    size: 'md',
    disabled: false,
    label: undefined,
    error: undefined,
});

const emit = defineEmits<{
    'upload': [file: File];
    'remove': [];
}>();

const sizeMap = {
    sm: 'h-16 w-16',
    md: 'h-24 w-24',
    lg: 'h-32 w-32',
};

const fileInput = ref<HTMLInputElement | null>(null);
const isDragging = ref(false);

function onFileChange(e: Event) {
    const input = e.target as HTMLInputElement;
    const file = input.files?.[0];
    if (file) emit('upload', file);
    if (input) input.value = '';
}

function onDrop(e: DragEvent) {
    isDragging.value = false;
    const file = e.dataTransfer?.files[0];
    if (file && file.type.startsWith('image/')) emit('upload', file);
}

function onDragOver(e: DragEvent) {
    e.preventDefault();
    isDragging.value = true;
}
</script>

<template>
    <div class="flex flex-col items-center gap-2">
        <div class="relative">
            <!-- Circle -->
            <div
                :class="[
                    sizeMap[size],
                    'relative rounded-full overflow-hidden cursor-pointer border-2 border-border bg-muted group',
                    isDragging && 'border-primary',
                    disabled && 'opacity-50 pointer-events-none',
                ]"
                @click="fileInput?.click()"
                @dragover="onDragOver"
                @dragleave="isDragging = false"
                @drop.prevent="onDrop"
            >
                <!-- Image or placeholder -->
                <img
                    v-if="modelValue"
                    :src="modelValue"
                    alt="Imagen"
                    class="h-full w-full object-cover"
                />
                <div
                    v-else
                    class="flex h-full w-full items-center justify-center"
                >
                    <ImageIcon class="h-1/3 w-1/3 text-muted-foreground" />
                </div>

                <!-- Hover overlay -->
                <div class="absolute inset-0 flex flex-col items-center justify-center gap-1 rounded-full bg-black/50 opacity-0 transition-opacity group-hover:opacity-100">
                    <Camera class="h-5 w-5 text-white" />
                    <span class="text-xs font-medium text-white">{{ modelValue ? 'Cambiar' : 'Subir' }}</span>
                </div>
            </div>

            <!-- Remove button -->
            <button
                v-if="modelValue && !disabled"
                type="button"
                class="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-destructive text-white shadow hover:bg-destructive/90"
                @click.stop="emit('remove')"
            >
                <X class="h-3 w-3" />
            </button>
        </div>

        <!-- Label -->
        <span v-if="label" class="text-xs text-muted-foreground">{{ label }}</span>

        <!-- Error -->
        <p v-if="error" class="text-xs text-destructive">{{ error }}</p>

        <!-- Hidden file input -->
        <input
            ref="fileInput"
            type="file"
            accept="image/*"
            class="hidden"
            @change="onFileChange"
        />
    </div>
</template>
```

- [ ] **Step 2: Build to check for errors**

```bash
npm run build 2>&1 | tail -5
```

Expected: `✓ built in ...`

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/ImageUploadCircle.vue
git commit -m "feat: add ImageUploadCircle component"
```

---

## Task 7: CropModal Component

**Files:**
- Create: `resources/js/components/CropModal.vue`

- [ ] **Step 1: Create the component**

Create `resources/js/components/CropModal.vue`:

```vue
<script setup lang="ts">
import { ref } from 'vue';
import { Cropper, CircleStencil } from 'vue-advanced-cropper';
import 'vue-advanced-cropper/dist/style.css';
import { CropIcon, X } from 'lucide-vue-next';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    open: boolean;
    imageSrc: string | null;
}>();

const emit = defineEmits<{
    'confirm': [blob: Blob];
    'cancel': [];
    'update:open': [value: boolean];
}>();

const cropperRef = ref<InstanceType<typeof Cropper> | null>(null);

function confirm() {
    const canvas = cropperRef.value?.getResult()?.canvas;
    if (!canvas) return;

    canvas.toBlob((blob) => {
        if (blob) emit('confirm', blob);
    }, 'image/webp', 0.9);
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>Recortar imagen</DialogTitle>
            </DialogHeader>

            <div class="flex items-center justify-center overflow-hidden rounded-lg bg-muted" style="height: 320px;">
                <Cropper
                    v-if="imageSrc"
                    ref="cropperRef"
                    :src="imageSrc"
                    :stencil-component="CircleStencil"
                    :stencil-props="{ aspectRatio: 1 }"
                    class="h-full w-full"
                />
            </div>

            <DialogFooter class="gap-2">
                <Button variant="outline" @click="emit('cancel')">
                    <X class="h-4 w-4" />
                    Cancelar
                </Button>
                <Button @click="confirm">
                    <CropIcon class="h-4 w-4" />
                    Confirmar
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
```

- [ ] **Step 2: Build to check for errors**

```bash
npm run build 2>&1 | tail -5
```

Expected: `✓ built in ...`

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/CropModal.vue
git commit -m "feat: add CropModal component with vue-advanced-cropper"
```

---

## Task 8: Integrate Logo into Clinic Edit Page

**Files:**
- Modify: `resources/js/pages/Admin/Clinics/Edit.vue`
- Modify: `resources/js/types/index.ts` (ensure `logo_url` in Clinic prop type)

- [ ] **Step 1: Update Edit.vue**

In `resources/js/pages/Admin/Clinics/Edit.vue`:

Add imports at top of `<script setup>`:
```ts
import { ref } from 'vue';
import ImageUploadCircle from '@/components/ImageUploadCircle.vue';
import CropModal from '@/components/CropModal.vue';
import * as clinicRoutes from '@/actions/App/Http/Controllers/Admin/ClinicController';
```

Add to `props` clinic type:
```ts
logo_url?: string | null;
```

Add reactive state:
```ts
const cropOpen = ref(false);
const cropSrc = ref<string | null>(null);
const currentLogoUrl = ref<string | null>(props.clinic.logo_url ?? null);

function onFileSelected(file: File) {
    cropSrc.value = URL.createObjectURL(file);
    cropOpen.value = true;
}

function onCropConfirm(blob: Blob) {
    cropOpen.value = false;
    const formData = new FormData();
    formData.append('image', new File([blob], 'logo.webp', { type: 'image/webp' }));
    router.post(clinicRoutes.uploadLogo(props.clinic.id).url, formData, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            currentLogoUrl.value = null; // triggers reload via page props
        },
    });
}

function removeLogo() {
    router.delete(clinicRoutes.destroyLogo(props.clinic.id).url, {
        preserveScroll: true,
        onSuccess: () => { currentLogoUrl.value = null; },
    });
}
```

In template, add above the first form field section:
```vue
<div class="mb-6 flex justify-center">
    <ImageUploadCircle
        :model-value="currentLogoUrl"
        size="lg"
        label="Logo de la clínica"
        :error="form.errors.image"
        @upload="onFileSelected"
        @remove="removeLogo"
    />
    <CropModal
        :open="cropOpen"
        :image-src="cropSrc"
        @confirm="onCropConfirm"
        @cancel="cropOpen = false"
        @update:open="cropOpen = $event"
    />
</div>
```

Also add `router` to the `@inertiajs/vue3` import:
```ts
import { Head, useForm, router } from '@inertiajs/vue3';
```

- [ ] **Step 2: Update ClinicController.edit() to include logo_url**

In `app/Http/Controllers/Admin/ClinicController.php`, in the `edit()` method, ensure the clinic is passed with `logo_url` appended. The accessor is already in `$appends` so it will be included automatically.

Verify by checking that `Clinic::$appends` includes `'logo_url'`. (Done in Task 5.)

- [ ] **Step 3: Build and typecheck**

```bash
npm run build 2>&1 | tail -5 && npm run types:check 2>&1 | tail -5
```

Expected: both pass.

- [ ] **Step 4: Commit**

```bash
git add resources/js/pages/Admin/Clinics/Edit.vue
git commit -m "feat: add logo upload to clinic edit page"
```

---

## Task 9: Integrate Logo into Clinic Create Wizard

**Files:**
- Modify: `resources/js/components/domain/Clinic/ClinicWizard/StepIdentity.vue`
- Modify: `resources/js/pages/Admin/Clinics/Create.vue`
- Modify: `app/Http/Requests/Admin/StoreClinicRequest.php` (add optional logo)
- Modify: `app/Domain/Clinic/Actions/CreateClinicAction.php` (upload logo after creation)

- [ ] **Step 1: Add optional `logo` validation to StoreClinicRequest**

In `app/Http/Requests/Admin/StoreClinicRequest.php`, add to `rules()`:
```php
'logo' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
```

- [ ] **Step 2: Update CreateClinicAction to handle logo**

In `app/Domain/Clinic/Actions/CreateClinicAction.php`, inject `UploadClinicLogoAction` and accept optional file:

Add import:
```php
use App\Domain\Clinic\Actions\UploadClinicLogoAction;
use Illuminate\Http\UploadedFile;
```

Modify `handle()` signature to accept optional logo:
```php
public function handle(ClinicData $data, ?UploadedFile $logo = null): Clinic
```

At the end of `handle()`, after the clinic is created, add:
```php
if ($logo) {
    app(UploadClinicLogoAction::class)->handle($clinic, $logo);
}
```

- [ ] **Step 3: Pass logo from ClinicController::store()**

In `app/Http/Controllers/Admin/ClinicController.php`, in `store()`:
```php
$clinic = $action->handle($data, $request->file('logo'));
```

(Find the existing `$action->handle($data)` call and add the second argument.)

- [ ] **Step 4: Add logo upload to StepIdentity.vue**

In `resources/js/components/domain/Clinic/ClinicWizard/StepIdentity.vue`:

Add imports:
```ts
import ImageUploadCircle from '@/components/ImageUploadCircle.vue';
```

Update props type to include logo:
```ts
const props = defineProps<{
    form: {
        slug: string;
        commercial_name: string;
        legal_name: string;
        contact_email: string;
        contact_phone: string;
        primary_color: string;
        logo: File | null;
        errors: Record<string, string>;
    };
}>();
```

Add emit:
```ts
const emit = defineEmits<{
    'upload-logo': [file: File];
}>();
```

In template, add at the top of the `<div class="space-y-5">`:
```vue
<div class="flex justify-center pb-2">
    <ImageUploadCircle
        :model-value="form.logo ? URL.createObjectURL(form.logo) : null"
        size="lg"
        label="Logo de la clínica (opcional)"
        :error="form.errors.logo"
        @upload="emit('upload-logo', $event)"
        @remove="form.logo = null"
    />
</div>
```

- [ ] **Step 5: Wire CropModal in Create.vue**

In `resources/js/pages/Admin/Clinics/Create.vue`:

Add imports:
```ts
import CropModal from '@/components/CropModal.vue';
```

Add state:
```ts
const cropOpen = ref(false);
const cropSrc = ref<string | null>(null);

function onLogoSelected(file: File) {
    cropSrc.value = URL.createObjectURL(file);
    cropOpen.value = true;
}

function onCropConfirm(blob: Blob) {
    cropOpen.value = false;
    form.logo = new File([blob], 'logo.webp', { type: 'image/webp' });
}
```

In template, add `CropModal` after the closing `</Card>` tag:
```vue
<CropModal
    :open="cropOpen"
    :image-src="cropSrc"
    @confirm="onCropConfirm"
    @cancel="cropOpen = false"
    @update:open="cropOpen = $event"
/>
```

Wire `StepIdentity`'s `@upload-logo` event:
```vue
<StepIdentity :form="form" @upload-logo="onLogoSelected" />
```

- [ ] **Step 6: Build and typecheck**

```bash
npm run build 2>&1 | tail -5 && npm run types:check 2>&1 | tail -5
```

Expected: both pass.

- [ ] **Step 7: Run pint**

```bash
vendor/bin/pint app/Http/Requests/Admin/StoreClinicRequest.php app/Domain/Clinic/Actions/CreateClinicAction.php app/Http/Controllers/Admin/ClinicController.php 2>&1 | grep -v Xdebug | tail -3
```

- [ ] **Step 8: Commit**

```bash
git add app/Http/Requests/Admin/StoreClinicRequest.php app/Domain/Clinic/Actions/CreateClinicAction.php app/Http/Controllers/Admin/ClinicController.php resources/js/components/domain/Clinic/ClinicWizard/StepIdentity.vue resources/js/pages/Admin/Clinics/Create.vue
git commit -m "feat: add logo upload to clinic create wizard"
```

---

## Task 10: Integrate Avatar into Settings > Profile

**Files:**
- Modify: `resources/js/pages/Settings/Profile.vue`

- [ ] **Step 1: Update Profile.vue**

In `resources/js/pages/Settings/Profile.vue`:

Add imports:
```ts
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import ImageUploadCircle from '@/components/ImageUploadCircle.vue';
import CropModal from '@/components/CropModal.vue';
import * as profileRoutes from '@/actions/App/Http/Controllers/Settings/ProfileController';
```

Add state:
```ts
const cropOpen = ref(false);
const cropSrc = ref<string | null>(null);

function onFileSelected(file: File) {
    cropSrc.value = URL.createObjectURL(file);
    cropOpen.value = true;
}

function onCropConfirm(blob: Blob) {
    cropOpen.value = false;
    const formData = new FormData();
    formData.append('image', new File([blob], 'avatar.webp', { type: 'image/webp' }));
    router.post(profileRoutes.uploadAvatar().url, formData, {
        forceFormData: true,
        preserveScroll: true,
    });
}

function removeAvatar() {
    router.delete(profileRoutes.destroyAvatar().url, { preserveScroll: true });
}
```

In the template, add before the existing form fields (or replace existing avatar display if any):
```vue
<div class="flex justify-center pb-4">
    <ImageUploadCircle
        :model-value="user?.avatar ?? null"
        size="lg"
        label="Foto de perfil"
        @upload="onFileSelected"
        @remove="removeAvatar"
    />
    <CropModal
        :open="cropOpen"
        :image-src="cropSrc"
        @confirm="onCropConfirm"
        @cancel="cropOpen = false"
        @update:open="cropOpen = $event"
    />
</div>
```

Note: `user` is already computed from `page.props.auth.user` in Profile.vue. Since `User.$appends` now includes `avatar`, it arrives automatically in shared props.

- [ ] **Step 2: Build and typecheck**

```bash
npm run build 2>&1 | tail -5 && npm run types:check 2>&1 | tail -5
```

Expected: both pass.

- [ ] **Step 3: Commit**

```bash
git add resources/js/pages/Settings/Profile.vue
git commit -m "feat: add avatar upload to settings profile page"
```

---

## Task 11: Show Logo on Clinic Show + Index Pages

**Files:**
- Modify: `resources/js/pages/Admin/Clinics/Show.vue`
- Modify: `resources/js/components/domain/Clinic/ClinicCard.vue` (if used on Index)

- [ ] **Step 1: Add logo to Clinic Show header**

In `resources/js/pages/Admin/Clinics/Show.vue`, find the clinic header section and add:
```vue
<div class="flex items-center gap-4">
    <div v-if="clinic.logo_url" class="h-16 w-16 shrink-0 overflow-hidden rounded-full border border-border">
        <img :src="clinic.logo_url" :alt="clinic.commercial_name" class="h-full w-full object-cover" />
    </div>
    <div v-else class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full border border-border bg-muted">
        <ImageIcon class="h-6 w-6 text-muted-foreground" />
    </div>
    <!-- existing clinic name/title -->
</div>
```

Add `ImageIcon` to lucide imports.

Also update the `clinic` prop type to include `logo_url?: string | null`.

- [ ] **Step 2: Build and typecheck**

```bash
npm run build 2>&1 | tail -5 && npm run types:check 2>&1 | tail -5
```

Expected: both pass.

- [ ] **Step 3: Commit**

```bash
git add resources/js/pages/Admin/Clinics/Show.vue
git commit -m "feat: show clinic logo on show page"
```

---

## Task 12: Final Verification

- [ ] **Step 1: Run all tests**

```bash
php artisan test --compact 2>&1 | grep -v Xdebug | tail -10
```

Expected: all pass.

- [ ] **Step 2: Run Pint**

```bash
vendor/bin/pint --dirty 2>&1 | grep -v Xdebug | tail -5
```

- [ ] **Step 3: Run PHPStan**

```bash
vendor/bin/phpstan analyse --memory-limit=1G --level=6 2>&1 | grep -v Xdebug | tail -5
```

Expected: `[OK] No errors`

- [ ] **Step 4: Build + typecheck**

```bash
npm run build 2>&1 | tail -3 && npm run types:check 2>&1 | tail -3
```

- [ ] **Step 5: Final commit**

```bash
git add -A
git commit -m "feat: image uploads complete — clinic logo + user avatar with circular display"
```
