<?php

declare(strict_types=1);

namespace App\Domain\Pricing;

final class TravelInsurancePricer
{
    public const DAILY_RATE_CENTS = 300;

    public function price(int $days, PricingRegion $region): PriceBreakdown
    {
        if ($days < 1 || $days > TripDuration::MAX_DAYS) {
            throw new InvalidTripDuration('El viaje debe durar entre 1 y '.TripDuration::MAX_DAYS.' días.');
        }

        $baseCents = $days * self::DAILY_RATE_CENTS;
        $percent = $region->surchargePercent();
        $surchargeCents = (int) round($baseCents * $percent / 100, 0, PHP_ROUND_HALF_UP);

        return new PriceBreakdown(
            days: $days,
            dailyRate: new Money(self::DAILY_RATE_CENTS),
            base: new Money($baseCents),
            surchargePercent: $percent,
            surcharge: new Money($surchargeCents),
            total: new Money($baseCents + $surchargeCents),
            region: $region,
        );
    }
}
