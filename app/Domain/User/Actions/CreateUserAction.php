<?php

namespace App\Domain\User\Actions;

use App\Actions\UploadUserAvatarAction;
use App\Domain\Clinic\Models\ClinicBranch;
use App\Domain\User\DataTransferObjects\UserData;
use App\Domain\User\Events\UserCreated;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateUserAction
{
    public function __construct(
        private readonly AssignRoleAction $assignRole,
        private readonly SendInvitationEmailAction $sendInvitationEmail,
        private readonly UploadUserAvatarAction $uploadAvatar,
    ) {}

    public function handle(UserData $data, ClinicBranch $branch): User
    {
        return DB::transaction(function () use ($data, $branch): User {
            $user = User::create([
                'clinic_id' => current_clinic()->id,
                'branch_id' => $branch->id,
                'name' => $data->name,
                'email' => $data->email,
                'phone' => $data->phone,
                'password' => $data->password,
                'professional_license' => $data->professionalLicense,
                'is_active' => true,
            ]);

            if ($data->avatar) {
                $this->uploadAvatar->handle($user, $data->avatar);
            }

            foreach ($data->roles as $role) {
                $this->assignRole->handle($user, $role);
            }

            $this->sendInvitationEmail->handle($user);

            UserCreated::dispatch($user, auth()->user());

            Log::channel('security')->info('user_created', [
                'clinic_id' => $user->clinic_id,
                'user_id' => $user->id,
                'by_user_id' => auth()->id(),
            ]);

            return $user->load(['branch', 'roles']);
        });
    }
}
