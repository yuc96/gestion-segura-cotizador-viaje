<?php

use App\Domain\Pricing\InvalidTripDuration;
use App\Domain\Pricing\PricingRegion;
use App\Domain\Pricing\TravelInsurancePricer;

it('charges three dollars per day before any regional surcharge', function () {
    $price = (new TravelInsurancePricer)->price(10, PricingRegion::SouthAmerica);

    expect($price->days)->toBe(10)
        ->and($price->dailyRate->toDecimal())->toBe('3.00')
        ->and($price->base->cents)->toBe(3_000)
        ->and($price->surchargePercent)->toBe(0)
        ->and($price->surcharge->cents)->toBe(0)
        ->and($price->total->toDecimal())->toBe('30.00');
});

it('reproduces the ten day trip to Spain', function () {
    $price = (new TravelInsurancePricer)->price(10, PricingRegion::Europe);

    expect($price->base->toDecimal())->toBe('30.00')
        ->and($price->surchargePercent)->toBe(20)
        ->and($price->surcharge->toDecimal())->toBe('6.00')
        ->and($price->total->toDecimal())->toBe('36.00');
});

it('applies the published regional surcharge table', function (PricingRegion $region, int $percent, int $totalCents) {
    $price = (new TravelInsurancePricer)->price(10, $region);

    expect($region->surchargePercent())->toBe($percent)
        ->and($price->total->cents)->toBe($totalCents);
})->with([
    'South America' => [PricingRegion::SouthAmerica, 0, 3_000],
    'North America' => [PricingRegion::NorthAmerica, 15, 3_450],
    'Europe' => [PricingRegion::Europe, 20, 3_600],
    'Africa' => [PricingRegion::Africa, 20, 3_600],
    'Asia' => [PricingRegion::Asia, 25, 3_750],
    'Oceania' => [PricingRegion::Oceania, 25, 3_750],
    'Other' => [PricingRegion::Other, 25, 3_750],
]);

it('keeps fractional surcharges exact to the cent', function () {
    $price = (new TravelInsurancePricer)->price(7, PricingRegion::Asia);

    expect($price->base->toDecimal())->toBe('21.00')
        ->and($price->surcharge->toDecimal())->toBe('5.25')
        ->and($price->total->toDecimal())->toBe('26.25');
});

it('rejects a trip outside the allowed length', function (int $days) {
    expect(fn () => (new TravelInsurancePricer)->price($days, PricingRegion::Europe))
        ->toThrow(InvalidTripDuration::class);
})->with([0, -1, 366]);
