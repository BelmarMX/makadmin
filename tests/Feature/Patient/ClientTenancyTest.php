<?php

use App\Domain\Patient\Models\Client;

test('clinic a cannot access clinic b clients by show or update routes', function () {
    [$clinicA, $branchA] = task03ClinicContext();
    $adminA = task03ClinicAdmin($clinicA, $branchA);

    [$clinicB] = task03ClinicContext();
    $clientB = Client::factory()->create(['clinic_id' => $clinicB->id]);

    app()->instance('current.clinic', $clinicA);
    setPermissionsTeamId($clinicA->id);

    $this->actingAs($adminA)
        ->get(task03ClinicRoute('clinic.clients.show', $clinicA, ['client' => $clientB->id]))
        ->assertNotFound();

    $this->actingAs($adminA)
        ->put(task03ClinicRoute('clinic.clients.update', $clinicA, ['client' => $clientB->id]), [
            'name' => 'Intento',
        ])
        ->assertNotFound();
});
