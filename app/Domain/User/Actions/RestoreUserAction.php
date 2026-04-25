<?php

namespace App\Domain\User\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class RestoreUserAction
{
    public function handle(User $user): void
    {
        $user->update(['is_active' => true]);

        Log::channel('security')->info('user_restored', [
            'clinic_id' => $user->clinic_id,
            'user_id' => $user->id,
            'by_user_id' => auth()->id(),
        ]);
    }
}
