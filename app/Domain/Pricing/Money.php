<?php

declare(strict_types=1);

namespace App\Domain\Pricing;

use InvalidArgumentException;

final readonly class Money
{
    public function __construct(public int $cents)
    {
        if ($cents < 0) {
            throw new InvalidArgumentException('El monto no puede ser negativo.');
        }
    }

    public function toDecimal(): string
    {
        return sprintf('%d.%02d', intdiv($this->cents, 100), $this->cents % 100);
    }
}
