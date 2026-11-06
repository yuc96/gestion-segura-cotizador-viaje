<?php

use App\Domain\Pricing\InvalidTripDuration;
use App\Domain\Pricing\TripDuration;
use Carbon\CarbonImmutable;

it('counts the departure and return dates', function () {
    $days = (new TripDuration)->inclusiveDays(
        CarbonImmutable::parse('2026-01-01'),
        CarbonImmutable::parse('2026-01-10'),
    );

    expect($days)->toBe(10);
});

it('treats a same day trip as one day', function () {
    $days = (new TripDuration)->inclusiveDays(
        CarbonImmutable::parse('2026-06-15'),
        CarbonImmutable::parse('2026-06-15'),
    );

    expect($days)->toBe(1);
});

it('accepts a full year and rejects one day more', function () {
    $duration = new TripDuration;

    expect($duration->inclusiveDays(
        CarbonImmutable::parse('2026-01-01'),
        CarbonImmutable::parse('2026-12-31'),
    ))->toBe(365);

    expect(fn () => $duration->inclusiveDays(
        CarbonImmutable::parse('2026-01-01'),
        CarbonImmutable::parse('2027-01-01'),
    ))->toThrow(InvalidTripDuration::class);
});

it('rejects a return before the departure', function () {
    expect(fn () => (new TripDuration)->inclusiveDays(
        CarbonImmutable::parse('2026-05-10'),
        CarbonImmutable::parse('2026-05-01'),
    ))->toThrow(InvalidTripDuration::class, 'anterior a la salida');
});
