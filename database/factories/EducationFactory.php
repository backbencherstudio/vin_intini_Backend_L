<?php

namespace Database\Factories;

use App\Models\Education;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Education>
 */
class EducationFactory extends Factory
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
            'institution_id' => Institution::factory(),
            'degree' => $this->faker->randomElement(['Bachelor', 'Master', 'Diploma']),
            'field_study' => $this->faker->jobTitle(),
            'start_month' => $this->faker->randomElement(['January', 'February', 'March', 'April']),
            'start_year' => (string) $this->faker->year(),
            'end_month' => $this->faker->optional()->randomElement(['May', 'June', 'July', 'August']),
            'end_year' => $this->faker->optional()->year(),
            'grade' => $this->faker->optional()->randomElement(['A', 'A+', '3.5/4.0']),
            'description' => $this->faker->sentence(),
            'activities' => 'Club, Workshop',
            'is_current' => false,
            'skills_id' => [],
        ];
    }
}
