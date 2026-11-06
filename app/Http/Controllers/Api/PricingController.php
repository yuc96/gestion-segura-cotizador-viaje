<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Pricing\PricingRegion;
use App\Domain\Pricing\TravelInsurancePricer;
use App\Domain\Pricing\TripDuration;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class PricingController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'data' => [
                'currency' => 'USD',
                'daily_rate' => sprintf('%d.%02d', intdiv(TravelInsurancePricer::DAILY_RATE_CENTS, 100), TravelInsurancePricer::DAILY_RATE_CENTS % 100),
                'max_days' => TripDuration::MAX_DAYS,
                'regions' => array_map(fn (PricingRegion $region): array => [
                    'code' => $region->value,
                    'label' => $region->label(),
                    'surcharge_percent' => $region->surchargePercent(),
                ], PricingRegion::cases()),
            ],
        ]);
    }
}
