<?php

namespace Database\Factories;

use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Institution>
 */
class InstitutionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->company(),
            'logo' => null,
            'type' => $this->faker->randomElement(['University', 'College', 'Institute']),
            'country' => $this->faker->country(),
            'website' => $this->faker->url(),
        ];
    }
}
