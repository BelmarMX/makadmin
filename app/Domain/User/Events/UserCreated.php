<?php

namespace App\Domain\User\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

final class UserCreated
{
    use Dispatchable;

    public function __construct(
        public readonly User $user,
        public readonly ?User $actor,
    ) {}
}
