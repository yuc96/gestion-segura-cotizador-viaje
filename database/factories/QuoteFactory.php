<?php

namespace Database\Factories;

use App\Domain\PremiumCalculator;
use App\Domain\PricingRegion;
use App\Domain\QuoteStatus;
use App\Models\Insured;
use App\Models\Quote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quote>
 */
class QuoteFactory extends Factory
{
    protected $model = Quote::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $departure = now()->startOfDay()->addDays(30);
        $return = $departure->copy()->addDays(9);
        $price = app(PremiumCalculator::class)->price(10, PricingRegion::Europe);

        return [
            'insured_id' => Insured::factory(),
            'destination_code' => 'ES',
            'destination_name' => 'España',
            'destination_region' => 'Europe',
            'destination_subregion' => 'Southern Europe',
            'pricing_region' => PricingRegion::Europe,
            'destination_flag' => '🇪🇸',
            'departure_date' => $departure->toDateString(),
            'return_date' => $return->toDateString(),
            'trip_days' => $price->days,
            'daily_rate_cents' => $price->dailyRate->cents,
            'base_amount_cents' => $price->base->cents,
            'surcharge_percent' => $price->surchargePercent,
            'surcharge_amount_cents' => $price->surcharge->cents,
            'total_amount_cents' => $price->total->cents,
            'currency' => 'USD',
            'status' => QuoteStatus::Quoted,
            'contracted_at' => null,
        ];
    }

    public function contracted(): static
    {
        return $this->state(fn (): array => [
            'status' => QuoteStatus::Contracted,
            'contracted_at' => now(),
        ]);
    }
}
