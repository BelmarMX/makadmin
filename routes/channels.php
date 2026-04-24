<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function (User $user, int $id) {
    return (int) $user->id === $id;
});

Broadcast::channel('clinic.{clinicId}.{topic}', function (User $user, int $clinicId, string $topic) {
    if ($user->is_super_admin) {
        return true;
    }

    return (int) $user->clinic_id === $clinicId;
});
