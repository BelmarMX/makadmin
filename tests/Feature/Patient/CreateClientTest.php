<?php

use App\Domain\Patient\Models\Client;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('clinic admin can create a client', function () {
    [$clinic, $branch] = task03ClinicContext();
    $admin = task03ClinicAdmin($clinic, $branch);

    Storage::fake('public');

    $this->actingAs($admin)
        ->post(task03ClinicRoute('clinic.clients.store', $clinic), [
            'name' => 'Mariana Torres',
            'email' => 'mariana@example.test',
            'phone' => '5512345678',
            'curp' => 'TOAM900101MDFRRN09',
            'avatar' => UploadedFile::fake()->image('mariana.jpg'),
        ])
        ->assertRedirect();

    $client = Client::first();

    expect($client)->not->toBeNull()
        ->and($client?->clinic_id)->toBe($clinic->id)
        ->and($client?->name)->toBe('Mariana Torres')
        ->and($client?->avatar_path)->not->toBeNull();

    Storage::disk('public')->assertExists($client->avatar_path);
});

test('client email must be unique inside the same clinic', function () {
    [$clinic, $branch] = task03ClinicContext();
    $admin = task03ClinicAdmin($clinic, $branch);

    Client::factory()->create([
        'clinic_id' => $clinic->id,
        'email' => 'owner@example.test',
    ]);

    $this->actingAs($admin)
        ->post(task03ClinicRoute('clinic.clients.store', $clinic), [
            'name' => 'Otro Tutor',
            'email' => 'owner@example.test',
        ])
        ->assertSessionHasErrors('email');
});
