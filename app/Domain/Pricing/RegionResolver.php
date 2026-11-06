<?php

declare(strict_types=1);

namespace App\Domain\Pricing;

/**
 * REST Countries agrupa América en una sola región.
 * La tarifa distingue Sudamérica de Norteamérica, así que la subregión manda.
 */
final class RegionResolver
{
    public function resolve(?string $region, ?string $subregion): PricingRegion
    {
        $region = trim((string) $region);
        $subregion = trim((string) $subregion);

        if ($subregion === 'South America') {
            return PricingRegion::SouthAmerica;
        }

        if (in_array($subregion, ['North America', 'Northern America', 'Central America', 'Caribbean'], true)) {
            return PricingRegion::NorthAmerica;
        }

        return match ($region) {
            'Europe' => PricingRegion::Europe,
            'Asia' => PricingRegion::Asia,
            'Africa' => PricingRegion::Africa,
            'Oceania' => PricingRegion::Oceania,
            'Americas' => PricingRegion::NorthAmerica,
            default => PricingRegion::Other,
        };
    }
}
