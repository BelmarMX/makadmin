<?php

use App\Domain\Patient\Models\Client;
use App\Domain\Patient\Models\Patient;

test('clinic admin can create a patient from quick create flow', function () {
    [$clinic, $branch] = task03ClinicContext();
    $admin = task03ClinicAdmin($clinic, $branch);
    $client = Client::factory()->create(['clinic_id' => $clinic->id]);

    $this->actingAs($admin)
        ->post(task03ClinicRoute('clinic.patients.quick-store', $clinic), [
            'client_id' => $client->id,
            'name' => 'Frida',
            'sex' => 'female',
            'microchip' => '333333333333333',
        ])
        ->assertRedirect();

    $patient = Patient::query()->where('microchip', '333333333333333')->first();

    expect($patient)->not->toBeNull()
        ->and($patient?->clinic_id)->toBe($clinic->id)
        ->and($patient?->client_id)->toBe($client->id)
        ->and($patient?->name)->toBe('Frida');
});
