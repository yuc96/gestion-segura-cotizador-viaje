<?php

declare(strict_types=1);

namespace App\Domain\Pricing;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

final class TripDuration
{
    public const MAX_DAYS = 365;

    /**
     * Cuenta el día de salida y el día de regreso.
     * Del 1 al 10 son 10 días: diff de calendario + 1.
     */
    public function inclusiveDays(CarbonInterface $departure, CarbonInterface $return): int
    {
        $start = CarbonImmutable::instance($departure)->startOfDay();
        $end = CarbonImmutable::instance($return)->startOfDay();

        if ($end->lt($start)) {
            throw new InvalidTripDuration('La fecha de regreso no puede ser anterior a la salida.');
        }

        $days = $start->diff($end)->days + 1;

        if ($days > self::MAX_DAYS) {
            throw new InvalidTripDuration('El viaje no puede superar '.self::MAX_DAYS.' días.');
        }

        return $days;
    }
}
