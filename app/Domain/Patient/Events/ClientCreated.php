<?php

namespace App\Domain\Patient\Events;

use App\Domain\Patient\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

final class ClientCreated
{
    use Dispatchable;

    public function __construct(
        public readonly Client $client,
        public readonly ?User $actor,
    ) {}
}
