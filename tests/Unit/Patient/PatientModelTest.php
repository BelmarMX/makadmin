<?php

use App\Domain\Patient\Enums\PatientSex;
use App\Domain\Patient\Models\Client;
use App\Domain\Patient\Models\Patient;

test('patient exposes age accessor and enum cast', function () {
    [$clinic] = task03ClinicContext();
    $client = Client::factory()->create(['clinic_id' => $clinic->id]);

    $patient = Patient::factory()->create([
        'clinic_id' => $clinic->id,
        'client_id' => $client->id,
        'sex' => PatientSex::Female,
        'birth_date' => now()->subYears(2)->toDateString(),
    ]);

    expect($patient->sex)->toBeInstanceOf(PatientSex::class)
        ->and($patient->age)->toContain('año');
});

test('patient search and microchip scopes work', function () {
    [$clinic] = task03ClinicContext();
    $client = Client::factory()->create(['clinic_id' => $clinic->id]);

    $patient = Patient::factory()->create([
        'clinic_id' => $clinic->id,
        'client_id' => $client->id,
        'name' => 'Nina',
        'microchip' => '555555555555555',
    ]);

    expect(Patient::query()->byMicrochip('555555555555555')->first()?->id)->toBe($patient->id)
        ->and(Patient::query()->search('Nina')->first()?->id)->toBe($patient->id);
});
