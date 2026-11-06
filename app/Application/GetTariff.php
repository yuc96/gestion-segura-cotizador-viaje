<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\PremiumCalculator;
use App\Domain\PricingRegion;
use App\Domain\TripPeriod;

final class GetTariff
{
    /**
     * @return array{currency: string, daily_rate: string, max_days: int, regions: list<array{code: string, label: string, surcharge_percent: int}>}
     */
    public function handle(): array
    {
        $cents = PremiumCalculator::DAILY_RATE_CENTS;

        return [
            'currency' => 'USD',
            'daily_rate' => sprintf('%d.%02d', intdiv($cents, 100), $cents % 100),
            'max_days' => TripPeriod::MAX_DAYS,
            'regions' => array_map(fn (PricingRegion $region): array => [
                'code' => $region->value,
                'label' => $region->label(),
                'surcharge_percent' => $region->surchargePercent(),
            ], PricingRegion::cases()),
        ];
    }
}
