<?php

namespace App\Domain\Patient\Actions;

use App\Domain\Patient\Events\ClientDeactivated;
use App\Domain\Patient\Models\Client;
use Illuminate\Support\Facades\DB;

class DeactivateClientAction
{
    public function handle(Client $client): void
    {
        DB::transaction(function () use ($client): void {
            $client->patients()->each(function ($patient): void {
                $patient->update(['is_active' => false]);

                if (! $patient->trashed()) {
                    $patient->delete();
                }
            });

            $client->update(['is_active' => false]);

            if (! $client->trashed()) {
                $client->delete();
            }

            ClientDeactivated::dispatch($client, auth()->user());
        });
    }
}
