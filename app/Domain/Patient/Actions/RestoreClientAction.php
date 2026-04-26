<?php

namespace App\Domain\Patient\Actions;

use App\Domain\Patient\Models\Client;
use Illuminate\Support\Facades\DB;

class RestoreClientAction
{
    public function handle(Client $client): Client
    {
        return DB::transaction(function () use ($client): Client {
            if ($client->trashed()) {
                $client->restore();
            }

            $client->update(['is_active' => true]);

            return $client->fresh();
        });
    }
}
