<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Application\GetTariff;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class PricingController extends Controller
{
    public function show(GetTariff $action): JsonResponse
    {
        return response()->json([
            'data' => $action->handle(),
        ]);
    }
}
