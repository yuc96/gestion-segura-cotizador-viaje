<?php

namespace Database\Factories;

use App\Models\Insured;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Insured>
 */
class InsuredFactory extends Factory
{
    protected $model = Insured::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'identification' => fake()->unique()->numerify('##########'),
            'email' => fake()->unique()->safeEmail(),
            'birth_date' => fake()->dateTimeBetween('-60 years', '-20 years')->format('Y-m-d'),
        ];
    }
}
