<?php

use App\Domain\Exception\InvalidTripPeriod;
use App\Domain\TripPeriod;

it('counts the departure and return dates', function () {
    $trip = TripPeriod::inclusive(
        new DateTimeImmutable('2026-01-01'),
        new DateTimeImmutable('2026-01-10'),
    );

    expect($trip->days)->toBe(10);
});

it('treats a same day trip as one day', function () {
    $trip = TripPeriod::inclusive(
        new DateTimeImmutable('2026-06-15'),
        new DateTimeImmutable('2026-06-15'),
    );

    expect($trip->days)->toBe(1);
});

it('accepts a full year and rejects one day more', function () {
    expect(TripPeriod::inclusive(
        new DateTimeImmutable('2026-01-01'),
        new DateTimeImmutable('2026-12-31'),
    )->days)->toBe(365);

    expect(fn () => TripPeriod::inclusive(
        new DateTimeImmutable('2026-01-01'),
        new DateTimeImmutable('2027-01-01'),
    ))->toThrow(InvalidTripPeriod::class);
});

it('rejects a return before the departure', function () {
    expect(fn () => TripPeriod::inclusive(
        new DateTimeImmutable('2026-05-10'),
        new DateTimeImmutable('2026-05-01'),
    ))->toThrow(InvalidTripPeriod::class, 'anterior a la salida');
});
