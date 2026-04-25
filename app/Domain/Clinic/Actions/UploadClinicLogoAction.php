<?php

namespace App\Domain\Clinic\Actions;

use App\Contracts\Integrations\MediaStorage;
use App\Domain\Clinic\Models\Clinic;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class UploadClinicLogoAction
{
    public function __construct(private readonly MediaStorage $media) {}

    public function handle(Clinic $clinic, UploadedFile $file): void
    {
        if ($clinic->logo_path) {
            $this->media->delete($clinic->logo_path);
        }

        $path = "logos/clinics/{$clinic->id}/logo_".Str::uuid().'.webp';

        $manager = new ImageManager(new Driver());
        $webp = (string) $manager->read($file->getRealPath())->cover(400, 400)->toWebp(80);

        $this->media->putRaw($path, $webp);

        $clinic->logo_path = $path;
        $clinic->save();
    }
}
