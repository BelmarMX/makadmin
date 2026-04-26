<?php

use App\Domain\Patient\Models\Client;
use App\Domain\Patient\Models\Patient;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('clinic admin can create a patient for a client', function () {
    [$clinic, $branch] = task03ClinicContext();
    $admin = task03ClinicAdmin($clinic, $branch);
    $client = Client::factory()->create(['clinic_id' => $clinic->id]);

    Storage::fake('public');

    $this->actingAs($admin)
        ->post(task03ClinicRoute('clinic.clients.patients.store', $clinic, ['client' => $client->id]), [
            'name' => 'Luna',
            'sex' => 'female',
            'microchip' => '123456789012345',
            'is_sterilized' => true,
            'photo' => UploadedFile::fake()->image('luna.jpg'),
        ])
        ->assertRedirect();

    $patient = Patient::first();

    expect($patient)->not->toBeNull()
        ->and($patient?->client_id)->toBe($client->id)
        ->and($patient?->clinic_id)->toBe($clinic->id)
        ->and($patient?->photo_path)->not->toBeNull();

    Storage::disk('public')->assertExists($patient->photo_path);
});

test('patient microchip must be unique inside the same clinic', function () {
    [$clinic, $branch] = task03ClinicContext();
    $admin = task03ClinicAdmin($clinic, $branch);
    $client = Client::factory()->create(['clinic_id' => $clinic->id]);

    Patient::factory()->create([
        'clinic_id' => $clinic->id,
        'client_id' => $client->id,
        'microchip' => '999999999999999',
    ]);

    $this->actingAs($admin)
        ->post(task03ClinicRoute('clinic.clients.patients.store', $clinic, ['client' => $client->id]), [
            'name' => 'Milo',
            'sex' => 'male',
            'microchip' => '999999999999999',
        ])
        ->assertSessionHasErrors('microchip');
});
