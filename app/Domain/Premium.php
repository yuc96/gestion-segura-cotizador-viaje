<?php

declare(strict_types=1);

namespace App\Domain;

final readonly class Premium
{
    public function __construct(
        public int $days,
        public Money $dailyRate,
        public Money $base,
        public int $surchargePercent,
        public Money $surcharge,
        public Money $total,
        public PricingRegion $region,
    ) {}
}
