<?php

namespace Database\Seeders;

use App\Domain\Country as CountryData;
use App\Infrastructure\Countries\BundledCountryCatalog;
use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $rows = array_map(fn (CountryData $country): array => [
            'code' => $country->code,
            'name' => $country->name,
            'name_es' => $country->name,
            'name_en' => $country->nameEn !== '' ? $country->nameEn : null,
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
                ['name', 'name_es', 'name_en', 'region', 'subregion', 'pricing_region', 'flag_emoji', 'updated_at'],
            );
        }
    }
}
