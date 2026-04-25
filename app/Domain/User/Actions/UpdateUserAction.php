<?php

namespace App\Domain\User\Actions;

use App\Actions\UploadUserAvatarAction;
use App\Domain\User\DataTransferObjects\UserData;
use App\Domain\User\Events\UserUpdated;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateUserAction
{
    public function __construct(
        private readonly UploadUserAvatarAction $uploadAvatar,
    ) {}

    public function handle(User $user, UserData $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $user->update([
                'branch_id' => $data->branchId,
                'name' => $data->name,
                'email' => $data->email,
                'phone' => $data->phone,
                'professional_license' => $data->professionalLicense,
                ...($data->password ? ['password' => $data->password] : []),
            ]);

            if ($data->avatar) {
                $this->uploadAvatar->handle($user, $data->avatar);
            }

            if ($data->roles !== []) {
                setPermissionsTeamId($user->clinic_id);
                $user->syncRoles($data->roles);
                $user->unsetRelation('roles');
            }

            UserUpdated::dispatch($user, auth()->user());

            return $user->fresh(['branch', 'roles']);
        });
    }
}
