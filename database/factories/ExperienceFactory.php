<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Experience;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Experience>
 */
class ExperienceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'company_id' => Company::factory(),
            'title' => fake()->jobTitle(),
            'employment_type' => fake()->optional()->randomElement(['Full-time', 'Part-time', 'Contract']),
            'location' => fake()->optional()->city(),
            'location_type' => fake()->optional()->randomElement(['On-site', 'Remote', 'Hybrid']),
            'start_date' => fake()->dateTimeBetween('-5 years', '-1 year'),
            'end_date' => null,
            'is_current' => true,
            'description' => fake()->optional()->paragraph(),
            'skills_id' => [],
        ];
    }

    public function ended(): static
    {
        return $this->state(function () {
            $start = fake()->dateTimeBetween('-5 years', '-2 years');

            return [
                'start_date' => $start,
                'end_date' => fake()->dateTimeBetween($start, '-1 year'),
                'is_current' => false,
            ];
        });
    }
}
