<?php

namespace Database\Factories;

use App\Domain\Clinic\Models\Clinic;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Clinic>
 */
class ClinicFactory extends Factory
{
    protected $model = Clinic::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 9999),
            'commercial_name' => $name,
            'legal_name' => $name.' SA de CV',
            'responsible_vet_name' => 'Dr. '.fake()->name(),
            'responsible_vet_license' => (string) fake()->numerify('########'),
            'contact_phone' => fake()->numerify('55########'),
            'contact_email' => fake()->unique()->safeEmail(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
