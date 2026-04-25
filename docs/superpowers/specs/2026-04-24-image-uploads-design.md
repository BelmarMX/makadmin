# Image Uploads — Clinic Logo + User Avatar

**Date:** 2026-04-24  
**Scope:** Clinic logo upload in admin panel + user avatar upload in settings. Circular display everywhere. Cache-busting filenames.

---

## 1. Architecture

```
File select → CropModal (vue-advanced-cropper, CircleStencil)
  → Blob → Inertia POST (multipart)
  → Action (Intervention Image → cover 400×400 → WebP)
  → MediaStorage::putRaw(path_with_uuid, webp_contents)
  → logo_path / avatar_path saved in DB
  → logoUrl / avatar accessor resolves URL via MediaStorage::url()
  → Frontend displays via CSS object-cover in rounded-full container
```

**New packages:**
- PHP: `intervention/image` v3 (image processing)
- JS: `vue-advanced-cropper` (interactive circular crop modal)

Both justified by this feature; no alternative without them at this quality level.

---

## 2. MediaStorage Contract Extension

Add `putRaw` to `app/Contracts/Integrations/MediaStorage.php`:

```php
public function putRaw(string $path, string $contents): string;
```

Implement in `LocalMediaStorage`:
```php
public function putRaw(string $path, string $contents): string
{
    Storage::disk('public')->put($path, $contents);
    return $path;
}
```

---

## 3. File Paths & Cache Busting

Filenames include a UUID so each upload produces a new URL, invalidating browser cache automatically.

- Clinic logos: `logos/clinics/{clinic_id}/logo_{uuid}.webp`
- User avatars: `avatars/users/{user_id}/avatar_{uuid}.webp`

Old file deleted via `MediaStorage::delete(old_path)` before writing new one.

---

## 4. Backend

### New Actions

**`app/Domain/Clinic/Actions/UploadClinicLogoAction.php`**
- Receives: `Clinic $clinic`, `UploadedFile $file`
- Deletes old `$clinic->logo_path` if set
- Generates UUID path: `logos/clinics/{id}/logo_{uuid}.webp`
- Converts via Intervention Image: `read($file)->cover(400, 400)->toWebp(80)`
- Calls `MediaStorage::putRaw($path, $webp)`
- Updates `$clinic->logo_path = $path` and saves

**`app/Actions/UploadUserAvatarAction.php`**
- Same pattern for `User $user`, `avatar_path`

### FormRequest

**`app/Http/Requests/UploadImageRequest.php`** (reusable for both)
```php
'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120']
```

### Controller Methods

**`ClinicController`** gets two new methods:
- `uploadLogo(UploadImageRequest $request, Clinic $clinic)` → calls `UploadClinicLogoAction`
- `destroyLogo(Clinic $clinic)` → deletes file, sets `logo_path = null`

**`ProfileController`** (settings) gets two new methods:
- `uploadAvatar(UploadImageRequest $request)` → calls `UploadUserAvatarAction` with `auth()->user()`
- `destroyAvatar()` → deletes file, sets `avatar_path = null`

### New Routes

```php
// Admin — clinic logo
POST   /clinics/{clinic}/logo      admin.clinics.upload-logo
DELETE /clinics/{clinic}/logo      admin.clinics.destroy-logo

// Settings — user avatar  
POST   /settings/profile/avatar          settings.profile.avatar.store
DELETE /settings/profile/avatar          settings.profile.avatar.destroy
```

### Model Accessors

**`Clinic`** — computed accessor (not stored):
```php
protected function logoUrl(): Attribute {
    return Attribute::make(
        get: fn () => $this->logo_path
            ? app(MediaStorage::class)->url($this->logo_path)
            : null
    );
}
```

**`User`** — maps existing `avatar` accessor used by `UserInfo.vue`:
```php
protected function avatar(): Attribute {
    return Attribute::make(
        get: fn () => $this->avatar_path
            ? app(MediaStorage::class)->url($this->avatar_path)
            : null
    );
}
```

`logo_url` and `avatar` must be appended to Inertia shared props where needed.

---

## 5. Frontend

### New Components

**`resources/js/components/ImageUploadCircle.vue`**

Props:
```ts
modelValue: string | null   // current image URL
size: 'sm' | 'md' | 'lg'   // sm=64px md=96px lg=128px
disabled?: boolean
label?: string              // text below circle e.g. "Logo de la clínica"
```

Emits: `upload(file: File)`, `remove()`

Behavior:
- Circle with `rounded-full overflow-hidden` + `object-cover`
- When `modelValue` is null: shows `ImageIcon` (lucide) centered, muted background
- Hover overlay: semi-transparent with camera icon + "Cambiar" text
- Click anywhere on circle → triggers hidden `<input type="file" accept="image/*">`
- Drag & drop on circle also accepted
- Small "×" button top-right when image exists → emits `remove`
- Error slot below circle for Inertia validation errors

**`resources/js/components/CropModal.vue`**

Props: `open: boolean`, `imageSrc: string | null`  
Emits: `confirm(blob: Blob)`, `cancel()`

Uses `vue-advanced-cropper` with `CircleStencil`. On confirm: calls `cropper.getCanvas()?.toBlob(cb, 'image/webp', 0.9)` → emits Blob.

### Page Integration

**`StepIdentity.vue`** (clinic wizard Create):
- Add `ImageUploadCircle` above the form fields
- On `@upload`: open `CropModal`
- On `@confirm`: store Blob in a `ref`. After the wizard POSTs and the clinic is created (success redirect), the Blob is uploaded immediately via a second POST to `admin.clinics.upload-logo`. If the user skips the logo, the clinic is created without one and can add it later from Edit.

**`Admin/Clinics/Edit.vue`**:
- Same `ImageUploadCircle` + `CropModal`
- On `@confirm`: immediately POST to `admin.clinics.upload-logo`

**`Settings/Profile.vue`**:
- Replace current avatar display with `ImageUploadCircle` (size="lg")
- On `@confirm`: POST to `settings.profile.avatar.store`
- On `@remove`: DELETE to `settings.profile.avatar.destroy`

### Sidebar (already works)

`UserInfo.vue` already renders `user.avatar` with `AvatarImage` fallback to initials. Once the `avatar` accessor is added to `User` and shared via `HandleInertiaRequests`, the sidebar updates automatically.

---

## 6. Inertia Shared Props

`HandleInertiaRequests::share()` must include `logo_url` in clinic context and ensure `auth.user.avatar` is populated. Check that `avatar` is appended in `User::$appends` or explicitly included in the shared user array.

---

## 7. Tests

```
tests/Feature/Admin/Clinic/UploadClinicLogoTest.php
  — super admin can upload logo → file stored, logo_path updated
  — old logo deleted when new uploaded
  — rejects invalid file type (PDF)
  — rejects file > 5MB
  — super admin can remove logo

tests/Feature/Settings/UploadAvatarTest.php
  — authenticated user can upload avatar
  — old avatar deleted when new uploaded
  — rejects invalid file
  — user can remove avatar
```

`MediaStorage` mocked in all tests — no disk writes.

---

## 8. Validation Rules (summary)

| Field  | Rules |
|--------|-------|
| image  | required, file, image, mimes:jpg,jpeg,png,gif,webp, max:5120 (5MB) |

---

## 9. Out of Scope

- S3 implementation of MediaStorage (LocalMediaStorage sufficient for MVP)
- Image moderation / content scanning
- Multiple logos per clinic
- Animated GIF preservation (converted to static WebP)
