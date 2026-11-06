<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exception\InvalidTripPeriod;

final class PremiumCalculator
{
    public const DAILY_RATE_CENTS = 300;

    public function price(int $days, PricingRegion $region): Premium
    {
        if ($days < 1 || $days > TripPeriod::MAX_DAYS) {
            throw new InvalidTripPeriod('El viaje debe durar entre 1 y '.TripPeriod::MAX_DAYS.' días.');
        }

        $baseCents = $days * self::DAILY_RATE_CENTS;
        $percent = $region->surchargePercent();
        $surchargeCents = (int) round($baseCents * $percent / 100, 0, PHP_ROUND_HALF_UP);

        return new Premium(
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
