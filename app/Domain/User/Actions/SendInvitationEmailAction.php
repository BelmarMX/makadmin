<?php

namespace App\Domain\User\Actions;

use App\Domain\Clinic\Models\Clinic;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Password;

class SendInvitationEmailAction
{
    public function handle(User $user): void
    {
        ResetPassword::createUrlUsing(function (User $notifiable, string $token): string {
            $clinic = $notifiable->clinic;
            $baseUrl = $clinic instanceof Clinic
                ? config('branding.scheme').'://'.$clinic->slug.'.'.config('branding.apex_domain')
                : config('app.url');

            return "{$baseUrl}/reset-password/{$token}?email={$notifiable->email}";
        });

        Password::broker()->sendResetLink(['email' => $user->email]);
    }
}
