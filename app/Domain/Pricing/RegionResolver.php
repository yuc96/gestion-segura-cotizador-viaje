<?php

declare(strict_types=1);

namespace App\Domain\Pricing;

/**
 * La tarifa distingue Sudamérica de Norteamérica.
 * Si hay subregión, manda ella. Si no, se usa la región o el continente.
 */
final class RegionResolver
{
    public function resolve(?string $region, ?string $subregion): PricingRegion
    {
        $region = trim((string) $region);
        $subregion = trim((string) $subregion);
        $place = $subregion !== '' ? $subregion : $region;

        if ($place === 'South America') {
            return PricingRegion::SouthAmerica;
        }

        if (in_array($place, ['North America', 'Northern America', 'Central America', 'Caribbean'], true)) {
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
