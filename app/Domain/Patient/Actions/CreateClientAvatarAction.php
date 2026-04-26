<?php

namespace App\Domain\Patient\Actions;

use App\Contracts\Integrations\MediaStorage;
use App\Domain\Patient\Models\Client;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class CreateClientAvatarAction
{
    public function __construct(private readonly MediaStorage $media) {}

    public function handle(Client $client, UploadedFile $file): void
    {
        if ($client->avatar_path) {
            $this->media->delete($client->avatar_path);
        }

        $path = "avatars/clients/{$client->id}/avatar_".Str::uuid().'.webp';

        $manager = new ImageManager(new Driver);
        $webp = (string) $manager->read($file->getRealPath())->cover(400, 400)->toWebp(80);

        $this->media->putRaw($path, $webp);

        $client->forceFill(['avatar_path' => $path])->save();
    }
}
