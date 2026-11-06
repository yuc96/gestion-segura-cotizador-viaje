<?php

declare(strict_types=1);

namespace App\Infrastructure\Countries;

use App\Domain\Country;
use App\Domain\RegionSurchargePolicy;
use Illuminate\Support\Facades\Log;

final class BundledCountryCatalog
{
    public function __construct(private RegionSurchargePolicy $regions) {}

    /**
     * @return list<Country>
     */
    public function all(): array
    {
        $path = database_path('data/countries.json');

        if (! is_file($path)) {
            Log::error('countries.snapshot.missing', ['path' => $path]);

            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        if (! is_array($decoded) || ! is_array($decoded['countries'] ?? null)) {
            Log::error('countries.snapshot.invalid', ['path' => $path]);

            return [];
        }

        $countries = [];

        foreach ($decoded['countries'] as $row) {
            if (! is_array($row)) {
                continue;
            }

            $code = strtoupper((string) ($row['code'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));

            if (preg_match('/^[A-Z]{2}$/', $code) !== 1 || $name === '') {
                continue;
            }

            $region = trim((string) ($row['region'] ?? ''));
            $subregion = trim((string) ($row['subregion'] ?? ''));

            $countries[$code] = new Country(
                code: $code,
                name: $name,
                region: $region,
                subregion: $subregion !== '' ? $subregion : null,
                pricingRegion: $this->regions->resolve($region, $subregion),
                flagEmoji: (string) ($row['flag_emoji'] ?? ''),
            );
        }

        return array_values($countries);
    }
}
