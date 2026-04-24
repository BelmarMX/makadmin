<?php

namespace App\Domain\Clinic\Actions;

use App\Domain\Clinic\DataTransferObjects\ClinicAdminInvitationData;
use App\Domain\Clinic\Models\Clinic;
use App\Domain\Clinic\Models\ClinicBranch;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class InviteClinicAdminAction
{
    public function handle(
        ClinicAdminInvitationData $data,
        Clinic $clinic,
        ClinicBranch $mainBranch,
    ): User {
        $user = User::create([
            'name' => $data->name,
            'email' => $data->email,
            'phone' => $data->phone,
            'clinic_id' => $clinic->id,
            'branch_id' => $mainBranch->id,
            'password' => Hash::make(Str::random(32)),
            'is_super_admin' => false,
        ]);

        setPermissionsTeamId($clinic->id);
        $role = Role::where('name', 'clinic_admin')->first();
        if ($role) {
            $user->assignRole($role);
        }

        ResetPassword::createUrlUsing(fn (User $u, string $token) => url("/reset-password/{$token}?email={$u->email}"));

        Password::broker()->sendResetLink(['email' => $user->email]);

        return $user;
    }
}
