<?php

use App\Domain\Patient\Models\Client;

test('client active and search scopes work', function () {
    [$clinic] = task03ClinicContext();

    $active = Client::factory()->create(['clinic_id' => $clinic->id, 'name' => 'Mario Tutor', 'is_active' => true]);
    $inactive = Client::factory()->inactive()->create(['clinic_id' => $clinic->id, 'name' => 'Lucia Tutor']);

    expect(Client::query()->active()->pluck('id')->all())
        ->toContain($active->id)
        ->not->toContain($inactive->id);

    expect(Client::query()->search('Mario')->pluck('id')->all())
        ->toContain($active->id);
});
