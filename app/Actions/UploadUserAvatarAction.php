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

        $path = "avatars/users/{$user->id}/avatar_".Str::uuid().'.webp';

        $manager = new ImageManager(new Driver());
        $webp = (string) $manager->read($file->getRealPath())->cover(400, 400)->toWebp(80);

        $this->media->putRaw($path, $webp);

        $user->avatar_path = $path;
        $user->save();
    }
}
