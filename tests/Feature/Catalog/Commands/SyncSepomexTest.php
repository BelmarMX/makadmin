<?php

use App\Domain\Catalog\Geographic\Models\Country;
use App\Domain\Catalog\Geographic\Models\Municipality;
use App\Domain\Catalog\Geographic\Models\PostalCode;
use App\Domain\Catalog\Geographic\Models\State;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $country = Country::create(['iso2' => 'MX', 'iso3' => 'MEX', 'name' => 'México', 'phone_code' => '+52']);
    $state = State::create(['id' => 1, 'country_id' => $country->id, 'name' => 'Aguascalientes', 'is_active' => true]);
    Municipality::create(['id' => 1, 'state_id' => $state->id, 'name' => 'Aguascalientes', 'is_active' => true]);
});

test('sync command inserts valid rows from fixture file', function () {
    $fixture = base_path('tests/Fixtures/sepomex_sample.txt');

    $this->artisan('catalog:sync-sepomex', ['--file' => $fixture])
        ->assertSuccessful();

    // 10 valid rows in fixture (line with MunicipioInvalido should fail to match)
    expect(PostalCode::count())->toBe(11);
});

test('sync command logs unmatched rows and does not abort', function () {
    $fixture = base_path('tests/Fixtures/sepomex_sample.txt');

    $this->artisan('catalog:sync-sepomex', ['--file' => $fixture])
        ->assertSuccessful()
        ->expectsOutputToContain('1 no coincidieron');
});

test('sync command with truncate clears table first', function () {
    PostalCode::insert([
        ['code' => '99999', 'state_id' => 1, 'municipality_id' => 1, 'settlement' => 'Old', 'settlement_type' => null, 'created_at' => now(), 'updated_at' => now()],
    ]);

    $fixture = base_path('tests/Fixtures/sepomex_sample.txt');

    $this->artisan('catalog:sync-sepomex', ['--file' => $fixture, '--truncate' => true])
        ->expectsConfirmation('¿Vaciar la tabla postal_codes antes de insertar?', 'yes')
        ->assertSuccessful();

    expect(PostalCode::where('settlement', 'Old')->count())->toBe(0);
    expect(PostalCode::count())->toBe(11);
});

test('sync command fails gracefully for missing file', function () {
    $this->artisan('catalog:sync-sepomex', ['--file' => '/tmp/nonexistent.txt'])
        ->assertFailed();
});
