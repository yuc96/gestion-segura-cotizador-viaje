<?php

namespace Database\Seeders;

use App\Domain\Countries\CountryData;
use App\Models\Country;
use App\Services\Countries\BundledCountryCatalog;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $rows = array_map(fn (CountryData $country): array => [
            'code' => $country->code,
            'name' => $country->name,
            'region' => $country->region,
            'subregion' => $country->subregion,
            'pricing_region' => $country->pricingRegion->value,
            'flag_emoji' => $country->flagEmoji,
            'created_at' => $now,
            'updated_at' => $now,
        ], app(BundledCountryCatalog::class)->all());

        foreach (array_chunk($rows, 100) as $chunk) {
            Country::query()->upsert(
                $chunk,
                ['code'],
                ['name', 'region', 'subregion', 'pricing_region', 'flag_emoji', 'updated_at'],
            );
        }
    }
}
