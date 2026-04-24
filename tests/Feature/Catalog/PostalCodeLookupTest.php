<?php

use App\Domain\Catalog\Geographic\Models\Country;
use App\Domain\Catalog\Geographic\Models\Municipality;
use App\Domain\Catalog\Geographic\Models\PostalCode;
use App\Domain\Catalog\Geographic\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $country = Country::create(['iso2' => 'MX', 'iso3' => 'MEX', 'name' => 'México', 'phone_code' => '+52']);
    $state = State::create(['id' => 1, 'country_id' => $country->id, 'name' => 'Jalisco', 'is_active' => true]);
    $mun = Municipality::create(['id' => 1, 'state_id' => $state->id, 'name' => 'Guadalajara', 'is_active' => true]);

    PostalCode::insert([
        ['code' => '44100', 'state_id' => $state->id, 'municipality_id' => $mun->id, 'settlement' => 'Centro', 'settlement_type' => 'Colonia', 'created_at' => now(), 'updated_at' => now()],
        ['code' => '44100', 'state_id' => $state->id, 'municipality_id' => $mun->id, 'settlement' => 'Analco', 'settlement_type' => 'Barrio', 'created_at' => now(), 'updated_at' => now()],
        ['code' => '44200', 'state_id' => $state->id, 'municipality_id' => $mun->id, 'settlement' => 'Jardines del Bosque', 'settlement_type' => 'Fraccionamiento', 'created_at' => now(), 'updated_at' => now()],
    ]);
});

test('postal code lookup by exact 5-digit code returns all settlements', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson(route('api.catalog.postal-codes', ['q' => '44100']))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment(['settlement' => 'Centro'])
        ->assertJsonFragment(['settlement' => 'Analco']);
});

test('postal code lookup by settlement name is case insensitive', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson(route('api.catalog.postal-codes', ['q' => 'centro']))
        ->assertOk()
        ->assertJsonFragment(['settlement' => 'Centro']);
})->skip(fn () => config('database.default') !== 'pgsql', 'ilike requires PostgreSQL');

test('postal code response includes state and municipality', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson(route('api.catalog.postal-codes', ['q' => '44100']))
        ->assertOk()
        ->assertJsonFragment(['name' => 'Jalisco'])
        ->assertJsonFragment(['name' => 'Guadalajara']);
});
