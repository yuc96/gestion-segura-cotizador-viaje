<?php

declare(strict_types=1);

namespace App\Http;

use App\Domain\Quote;

final class QuotePresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function make(Quote $quote): array
    {
        return [
            'id' => $quote->id,
            'number' => $quote->number(),
            'status' => $quote->status->value,
            'insured' => [
                'first_name' => $quote->insured->firstName,
                'last_name' => $quote->insured->lastName,
                'full_name' => $quote->insured->fullName(),
                'identification' => $quote->insured->identification,
                'email' => $quote->insured->email,
                'birth_date' => $quote->insured->birthDate,
            ],
            'destination' => [
                'code' => $quote->destination->code,
                'name' => $quote->destination->name,
                'region' => $quote->destination->region,
                'subregion' => $quote->destination->subregion,
                'pricing_region' => $quote->destination->pricingRegion->value,
                'pricing_region_label' => $quote->destination->pricingRegion->label(),
                'flag_emoji' => $quote->destination->flagEmoji,
            ],
            'departure_date' => $quote->trip->departure->format('Y-m-d'),
            'return_date' => $quote->trip->return->format('Y-m-d'),
            'trip_days' => $quote->trip->days,
            'pricing' => [
                'currency' => 'USD',
                'daily_rate' => $quote->premium->dailyRate->toDecimal(),
                'base_amount' => $quote->premium->base->toDecimal(),
                'surcharge_percent' => $quote->premium->surchargePercent,
                'surcharge_amount' => $quote->premium->surcharge->toDecimal(),
                'total_amount' => $quote->premium->total->toDecimal(),
            ],
            'contracted_at' => $quote->contractedAt?->format(DATE_ATOM),
            'created_at' => $quote->createdAt?->format(DATE_ATOM),
        ];
    }
}
