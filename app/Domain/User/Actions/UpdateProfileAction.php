<?php

namespace App\Domain\User\Actions;

use App\Actions\UploadUserAvatarAction;
use App\Domain\User\DataTransferObjects\UserData;
use App\Domain\User\Events\UserUpdated;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateProfileAction
{
    public function __construct(
        private readonly UploadUserAvatarAction $uploadAvatar,
    ) {}

    public function handle(User $user, UserData $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $user->update([
                'name' => $data->name,
                'phone' => $data->phone,
                ...($data->password ? ['password' => $data->password] : []),
            ]);

            if ($data->avatar) {
                $this->uploadAvatar->handle($user, $data->avatar);
            }

            UserUpdated::dispatch($user, $user);

            return $user->fresh();
        });
    }
}
