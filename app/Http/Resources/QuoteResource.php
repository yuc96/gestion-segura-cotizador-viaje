<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Quote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Quote
 */
class QuoteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number(),
            'status' => $this->status->value,
            'insured' => [
                'first_name' => $this->insured->first_name,
                'last_name' => $this->insured->last_name,
                'full_name' => $this->insured->fullName(),
                'identification' => $this->insured->identification,
                'email' => $this->insured->email,
                'birth_date' => $this->insured->birth_date?->toDateString(),
            ],
            'destination' => [
                'code' => $this->destination_code,
                'name' => $this->destination_name,
                'region' => $this->destination_region,
                'subregion' => $this->destination_subregion,
                'pricing_region' => $this->pricing_region->value,
                'pricing_region_label' => $this->pricing_region->label(),
                'flag_emoji' => $this->destination_flag,
            ],
            'departure_date' => $this->departure_date?->toDateString(),
            'return_date' => $this->return_date?->toDateString(),
            'trip_days' => $this->trip_days,
            'pricing' => [
                'currency' => $this->currency,
                'daily_rate' => $this->money($this->daily_rate_cents),
                'base_amount' => $this->money($this->base_amount_cents),
                'surcharge_percent' => $this->surcharge_percent,
                'surcharge_amount' => $this->money($this->surcharge_amount_cents),
                'total_amount' => $this->money($this->total_amount_cents),
            ],
            'contracted_at' => $this->contracted_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    private function money(int $cents): string
    {
        return sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }
}
