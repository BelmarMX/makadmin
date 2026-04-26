<?php

use App\Domain\Patient\Models\Client;
use App\Domain\Patient\Models\Patient;

test('patient search only returns current clinic patients', function () {
    [$clinicA, $branchA] = task03ClinicContext();
    $adminA = task03ClinicAdmin($clinicA, $branchA);
    $clientA = Client::factory()->create(['clinic_id' => $clinicA->id]);
    $patientA = Patient::factory()->create([
        'clinic_id' => $clinicA->id,
        'client_id' => $clientA->id,
        'name' => 'Kiara',
        'microchip' => '111111111111111',
        'is_active' => true,
    ]);

    [$clinicB] = task03ClinicContext();
    $clientB = Client::factory()->create(['clinic_id' => $clinicB->id]);
    Patient::factory()->create([
        'clinic_id' => $clinicB->id,
        'client_id' => $clientB->id,
        'name' => 'Kiara B',
        'microchip' => '222222222222222',
        'is_active' => true,
    ]);

    app()->instance('current.clinic', $clinicA);
    setPermissionsTeamId($clinicA->id);

    $this->actingAs($adminA)
        ->getJson(task03ClinicRoute('clinic.api.patients.search', $clinicA, ['q' => $patientA->microchip]))
        ->assertOk()
        ->assertJsonFragment(['id' => $patientA->id, 'name' => 'Kiara'])
        ->assertJsonMissing(['microchip' => '222222222222222']);
});

test('patient search requires at least two characters', function () {
    [$clinic, $branch] = task03ClinicContext();
    $admin = task03ClinicAdmin($clinic, $branch);

    $this->actingAs($admin)
        ->getJson(task03ClinicRoute('clinic.api.patients.search', $clinic, ['q' => 'a']))
        ->assertStatus(422);
});
