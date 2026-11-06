<?php

use App\Domain\PricingRegion;
use App\Domain\RegionSurchargePolicy;

it('maps regions onto the tariff table', function (?string $region, ?string $subregion, PricingRegion $expected) {
    expect((new RegionSurchargePolicy)->resolve($region, $subregion))->toBe($expected);
})->with([
    'Ecuador' => ['Americas', 'South America', PricingRegion::SouthAmerica],
    'United States' => ['Americas', 'North America', PricingRegion::NorthAmerica],
    'legacy northern america' => ['Americas', 'Northern America', PricingRegion::NorthAmerica],
    'Panama' => ['Americas', 'Central America', PricingRegion::NorthAmerica],
    'Cuba' => ['Americas', 'Caribbean', PricingRegion::NorthAmerica],
    'Spain' => ['Europe', 'Southern Europe', PricingRegion::Europe],
    'Japan' => ['Asia', 'Eastern Asia', PricingRegion::Asia],
    'South Africa' => ['Africa', 'Southern Africa', PricingRegion::Africa],
    'Australia' => ['Oceania', 'Australia and New Zealand', PricingRegion::Oceania],
    'Americas without subregion' => ['Americas', '', PricingRegion::NorthAmerica],
    'South America continent' => ['South America', '', PricingRegion::SouthAmerica],
    'North America continent' => ['North America', '', PricingRegion::NorthAmerica],
    'Antarctica' => ['Antarctic', '', PricingRegion::Other],
    'Antarctica continent' => ['Antarctica', null, PricingRegion::Other],
    'unknown' => ['', null, PricingRegion::Other],
]);
