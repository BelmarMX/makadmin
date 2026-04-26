<?php

namespace Database\Factories;

use App\Domain\Catalog\Veterinary\Models\Breed;
use App\Domain\Catalog\Veterinary\Models\PelageColor;
use App\Domain\Catalog\Veterinary\Models\Species;
use App\Domain\Catalog\Veterinary\Models\Temperament;
use App\Domain\Patient\Enums\PatientSex;
use App\Domain\Patient\Enums\PatientSize;
use App\Domain\Patient\Models\Client;
use App\Domain\Patient\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    protected $model = Patient::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'clinic_id' => fn (array $attributes): ?int => Client::withoutGlobalScopes()->find($attributes['client_id'])?->clinic_id,
            'species_id' => Species::query()->value('id'),
            'breed_id' => Breed::query()->value('id'),
            'temperament_id' => Temperament::query()->value('id'),
            'coat_color_id' => PelageColor::query()->value('id'),
            'name' => fake()->firstName(),
            'sex' => fake()->randomElement(PatientSex::cases()),
            'birth_date' => fake()->optional()->date(),
            'microchip' => fake()->optional()->regexify('[0-9]{15}'),
            'size' => fake()->optional()->randomElement(PatientSize::cases()),
            'weight_kg' => fake()->optional()->randomFloat(2, 0.5, 70),
            'photo_path' => null,
            'notes' => fake()->optional()->sentence(),
            'is_active' => true,
            'is_sterilized' => fake()->boolean(),
            'is_deceased' => false,
            'deceased_at' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
