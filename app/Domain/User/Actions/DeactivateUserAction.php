<?php

namespace App\Domain\User\Actions;

use App\Domain\User\Events\UserDeactivated;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class DeactivateUserAction
{
    public function handle(User $user): void
    {
        $user->update(['is_active' => false]);

        UserDeactivated::dispatch($user, auth()->user());

        Log::channel('security')->info('user_deactivated', [
            'clinic_id' => $user->clinic_id,
            'user_id' => $user->id,
            'by_user_id' => auth()->id(),
        ]);
    }
}
