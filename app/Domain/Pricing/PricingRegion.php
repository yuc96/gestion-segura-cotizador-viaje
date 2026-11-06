<?php

declare(strict_types=1);

namespace App\Domain\Pricing;

enum PricingRegion: string
{
    case SouthAmerica = 'South America';
    case NorthAmerica = 'North America';
    case Europe = 'Europe';
    case Asia = 'Asia';
    case Africa = 'Africa';
    case Oceania = 'Oceania';
    case Other = 'Other';

    public function surchargePercent(): int
    {
        return match ($this) {
            self::SouthAmerica => 0,
            self::NorthAmerica => 15,
            self::Europe, self::Africa => 20,
            self::Asia, self::Oceania, self::Other => 25,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::SouthAmerica => 'Sudamérica',
            self::NorthAmerica => 'Norteamérica',
            self::Europe => 'Europa',
            self::Asia => 'Asia',
            self::Africa => 'África',
            self::Oceania => 'Oceanía',
            self::Other => 'Otras regiones',
        };
    }
}
