<?php

namespace Database\Seeders;

use App\Domain\Clinic\Models\Clinic;
use App\Domain\Patient\Models\Client;
use App\Domain\Patient\Models\Patient;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        Clinic::query()->each(function (Clinic $clinic): void {
            app()->instance('current.clinic', $clinic);

            Client::factory()
                ->count(5)
                ->create(['clinic_id' => $clinic->id])
                ->each(function (Client $client): void {
                    Patient::factory()
                        ->count(fake()->numberBetween(1, 3))
                        ->create([
                            'clinic_id' => $client->clinic_id,
                            'client_id' => $client->id,
                        ]);
                });
        });
    }
}
