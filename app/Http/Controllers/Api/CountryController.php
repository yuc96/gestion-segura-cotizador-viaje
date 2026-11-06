<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Application\GetCountries;
use App\Domain\Country;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class CountryController extends Controller
{
    public function index(GetCountries $action): JsonResponse
    {
        $result = $action->handle();

        return response()->json([
            'data' => array_map(fn (Country $country): array => [
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
