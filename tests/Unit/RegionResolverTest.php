<?php

use App\Domain\Pricing\PricingRegion;
use App\Domain\Pricing\RegionResolver;

it('maps REST Countries regions onto the tariff table', function (?string $region, ?string $subregion, PricingRegion $expected) {
    expect((new RegionResolver)->resolve($region, $subregion))->toBe($expected);
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
    'Antarctica' => ['Antarctic', '', PricingRegion::Other],
    'unknown' => ['', null, PricingRegion::Other],
]);
