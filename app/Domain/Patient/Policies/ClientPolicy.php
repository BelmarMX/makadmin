<?php

namespace App\Domain\Patient\Policies;

use App\Domain\Patient\Models\Client;
use App\Domain\Patient\Permissions;
use App\Models\User;

class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permissions::CLIENTS_VIEW);
    }

    public function view(User $user, Client $client): bool
    {
        return $this->sameClinic($user, $client->clinic_id) && $user->can(Permissions::CLIENTS_VIEW);
    }

    public function create(User $user): bool
    {
        return $user->can(Permissions::CLIENTS_CREATE);
    }

    public function update(User $user, Client $client): bool
    {
        return $this->sameClinic($user, $client->clinic_id) && $user->can(Permissions::CLIENTS_UPDATE);
    }

    public function deactivate(User $user, Client $client): bool
    {
        return $this->sameClinic($user, $client->clinic_id) && $user->can(Permissions::CLIENTS_DEACTIVATE);
    }

    public function restore(User $user, Client $client): bool
    {
        return $this->sameClinic($user, $client->clinic_id) && $user->can(Permissions::CLIENTS_RESTORE);
    }

    private function sameClinic(User $user, ?int $clinicId): bool
    {
        return $user->clinic_id !== null && $user->clinic_id === $clinicId;
    }
}
