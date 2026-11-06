<?php

namespace Database\Factories;

use App\Domain\PricingRegion;
use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Country>
 */
class CountryFactory extends Factory
{
    protected $model = Country::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('??')),
            'name' => fake()->country(),
            'region' => 'Europe',
            'subregion' => 'Southern Europe',
            'pricing_region' => PricingRegion::Europe,
            'flag_emoji' => '🏳️',
        ];
    }
}
