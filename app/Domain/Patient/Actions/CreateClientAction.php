<?php

namespace App\Domain\Patient\Actions;

use App\Domain\Patient\DataTransferObjects\ClientData;
use App\Domain\Patient\Events\ClientCreated;
use App\Domain\Patient\Models\Client;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CreateClientAction
{
    public function __construct(private readonly CreateClientAvatarAction $uploadAvatar) {}

    public function handle(ClientData $data, ?UploadedFile $avatar = null): Client
    {
        return DB::transaction(function () use ($data, $avatar): Client {
            $client = Client::create([
                ...$data->toArray(),
                'clinic_id' => current_clinic()->id,
                'is_active' => true,
            ]);

            if ($avatar) {
                $this->uploadAvatar->handle($client, $avatar);
            }

            ClientCreated::dispatch($client, auth()->user());

            return $client->fresh();
        });
    }
}
