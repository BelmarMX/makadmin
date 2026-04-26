<?php

namespace App\Domain\Patient\Actions;

use App\Domain\Patient\DataTransferObjects\ClientData;
use App\Domain\Patient\Events\ClientUpdated;
use App\Domain\Patient\Models\Client;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class UpdateClientAction
{
    public function __construct(private readonly CreateClientAvatarAction $uploadAvatar) {}

    public function handle(Client $client, ClientData $data, ?UploadedFile $avatar = null): Client
    {
        return DB::transaction(function () use ($client, $data, $avatar): Client {
            $client->update($data->toArray());

            if ($avatar) {
                $this->uploadAvatar->handle($client, $avatar);
            }

            ClientUpdated::dispatch($client, auth()->user());

            return $client->fresh();
        });
    }
}
