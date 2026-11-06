<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Countries\CountryData;
use App\Http\Controllers\Controller;
use App\Services\Countries\CountryCatalog;
use Illuminate\Http\JsonResponse;

final class CountryController extends Controller
{
    public function index(CountryCatalog $catalog): JsonResponse
    {
        $result = $catalog->get();

        return response()->json([
            'data' => array_map(fn (CountryData $country): array => [
                'code' => $country->code,
                'name' => $country->name,
                'region' => $country->region,
                'subregion' => $country->subregion,
                'pricing_region' => $country->pricingRegion->value,
                'pricing_region_label' => $country->pricingRegion->label(),
                'surcharge_percent' => $country->pricingRegion->surchargePercent(),
                'flag_emoji' => $country->flagEmoji,
            ], $result->countries),
            'meta' => [
                'source' => $result->source,
                'notice' => $result->notice,
            ],
        ]);
    }
}
