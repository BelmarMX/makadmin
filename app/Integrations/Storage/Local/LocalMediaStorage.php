<?php

namespace App\Integrations\Storage\Local;

use App\Contracts\Integrations\MediaStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class LocalMediaStorage implements MediaStorage
{
    public function put(string $path, UploadedFile $file): string
    {
        $stored = $file->storeAs(
            dirname($path),
            basename($path).'.'.$file->extension(),
            'public',
        );

        return $stored ?: $path;
    }

    public function url(string $path): string
    {
        return Storage::disk('public')->url($path);
    }

    public function delete(string $path): bool
    {
        return Storage::disk('public')->delete($path);
    }
}
