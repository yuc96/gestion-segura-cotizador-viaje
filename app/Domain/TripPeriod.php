<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exception\InvalidTripPeriod;
use DateTimeImmutable;
use DateTimeInterface;

final readonly class TripPeriod
{
    public const MAX_DAYS = 365;

    public function __construct(
        public DateTimeImmutable $departure,
        public DateTimeImmutable $return,
        public int $days,
    ) {}

    /**
     * Cuenta el día de salida y el de regreso. Del 1 al 10 son 10 días.
     */
    public static function inclusive(DateTimeInterface $departure, DateTimeInterface $return): self
    {
        $start = DateTimeImmutable::createFromInterface($departure)->setTime(0, 0);
        $end = DateTimeImmutable::createFromInterface($return)->setTime(0, 0);

        if ($end < $start) {
            throw new InvalidTripPeriod('La fecha de regreso no puede ser anterior a la salida.');
        }

        $days = $start->diff($end)->days + 1;

        if ($days > self::MAX_DAYS) {
            throw new InvalidTripPeriod('El viaje no puede superar '.self::MAX_DAYS.' días.');
        }

        return new self($start, $end, $days);
    }
}
