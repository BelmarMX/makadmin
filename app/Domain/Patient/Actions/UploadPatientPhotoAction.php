<?php

namespace App\Domain\Patient\Actions;

use App\Contracts\Integrations\MediaStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class UploadPatientPhotoAction
{
    public function __construct(
        private readonly MediaStorage $mediaStorage,
    ) {}

    public function handle(UploadedFile $file, int $clinicId): string
    {
        return $this->mediaStorage->put("patients/{$clinicId}/".Str::uuid()->toString(), $file);
    }
}
