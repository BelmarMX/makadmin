<?php

namespace Database\Factories;

use App\Domain\Clinic\Models\Clinic;
use App\Domain\Patient\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'clinic_id' => Clinic::factory(),
            'name' => fake()->name(),
            'email' => fake()->optional()->safeEmail(),
            'phone' => fake()->optional()->numerify('55########'),
            'phone_alt' => fake()->optional()->numerify('55########'),
            'address' => fake()->optional()->streetAddress(),
            'colonia' => fake()->optional()->citySuffix(),
            'city' => fake()->optional()->city(),
            'state' => fake()->optional()->state(),
            'postal_code' => fake()->optional()->postcode(),
            'curp' => fake()->optional()->regexify('[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[A-Z0-9]{2}'),
            'rfc' => fake()->optional()->regexify('[A-Z&Ñ]{3,4}[0-9]{6}[A-Z0-9]{3}'),
            'notes' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
