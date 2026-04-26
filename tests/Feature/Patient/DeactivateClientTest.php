<?php

use App\Domain\Patient\Models\Client;
use App\Domain\Patient\Models\Patient;

test('deactivating a client deactivates and soft deletes their patients', function () {
    [$clinic, $branch] = task03ClinicContext();
    $admin = task03ClinicAdmin($clinic, $branch);

    $client = Client::factory()->create(['clinic_id' => $clinic->id]);
    $patient = Patient::factory()->create([
        'clinic_id' => $clinic->id,
        'client_id' => $client->id,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->post(task03ClinicRoute('clinic.clients.deactivate', $clinic, ['client' => $client->id]))
        ->assertRedirect();

    $storedClient = Client::withTrashed()->findOrFail($client->id);
    $storedPatient = Patient::withTrashed()->findOrFail($patient->id);

    expect($storedClient->is_active)->toBeFalse()
        ->and($storedClient->trashed())->toBeTrue()
        ->and($storedPatient->is_active)->toBeFalse()
        ->and($storedPatient->trashed())->toBeTrue();
});

test('restoring a client does not reactivate patients automatically', function () {
    [$clinic, $branch] = task03ClinicContext();
    $admin = task03ClinicAdmin($clinic, $branch);

    $client = Client::factory()->create(['clinic_id' => $clinic->id]);
    $patient = Patient::factory()->create([
        'clinic_id' => $clinic->id,
        'client_id' => $client->id,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->post(task03ClinicRoute('clinic.clients.deactivate', $clinic, ['client' => $client->id]));

    $this->actingAs($admin)
        ->post(task03ClinicRoute('clinic.clients.restore', $clinic, ['client' => $client->id]))
        ->assertRedirect();

    $storedClient = Client::findOrFail($client->id);
    $storedPatient = Patient::withTrashed()->findOrFail($patient->id);

    expect($storedClient->is_active)->toBeTrue()
        ->and($storedPatient->is_active)->toBeFalse();
});
