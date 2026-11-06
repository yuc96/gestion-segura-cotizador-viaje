<?php

use App\Domain\Country;
use App\Domain\Exception\QuoteAlreadyContracted;
use App\Domain\InsuredSnapshot;
use App\Domain\Money;
use App\Domain\Premium;
use App\Domain\PremiumCalculator;
use App\Domain\PricingRegion;
use App\Domain\Quote;
use App\Domain\QuoteStatus;
use App\Domain\TripPeriod;

it('contracts a quoted policy once', function () {
    $premium = (new PremiumCalculator)->price(10, PricingRegion::Europe);
    $quote = new Quote(
        id: 14,
        insured: new InsuredSnapshot('Ana', 'Torres', '1722334455', 'ana@example.com', '1990-04-12'),
        destination: new Country('ES', 'España', 'Europe', 'Southern Europe', PricingRegion::Europe, '🇪🇸'),
        trip: TripPeriod::inclusive(new DateTimeImmutable('2026-12-01'), new DateTimeImmutable('2026-12-10')),
        premium: $premium,
        status: QuoteStatus::Quoted,
        contractedAt: null,
        createdAt: new DateTimeImmutable('2026-11-06'),
    );

    $contracted = $quote->contract(new DateTimeImmutable('2026-11-06 10:00:00'));

    expect($contracted->status)->toBe(QuoteStatus::Contracted)
        ->and($contracted->premium)->toBeInstanceOf(Premium::class)
        ->and($contracted->premium->total)->toEqual(new Money(3600))
        ->and($quote->status)->toBe(QuoteStatus::Quoted);

    expect(fn () => $contracted->contract(new DateTimeImmutable('2026-11-07')))
        ->toThrow(QuoteAlreadyContracted::class);
});
