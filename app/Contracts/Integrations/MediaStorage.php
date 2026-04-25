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
