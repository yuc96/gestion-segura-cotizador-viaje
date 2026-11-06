<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Pricing\PricingRegion;
use App\Domain\Quotes\QuoteStatus;
use Database\Factories\QuoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'insured_id',
    'destination_code',
    'destination_name',
    'destination_region',
    'destination_subregion',
    'pricing_region',
    'destination_flag',
    'departure_date',
    'return_date',
    'trip_days',
    'daily_rate_cents',
    'base_amount_cents',
    'surcharge_percent',
    'surcharge_amount_cents',
    'total_amount_cents',
    'currency',
    'status',
    'contracted_at',
])]
class Quote extends Model
{
    /** @use HasFactory<QuoteFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pricing_region' => PricingRegion::class,
            'departure_date' => 'date',
            'return_date' => 'date',
            'trip_days' => 'integer',
            'daily_rate_cents' => 'integer',
            'base_amount_cents' => 'integer',
            'surcharge_percent' => 'integer',
            'surcharge_amount_cents' => 'integer',
            'total_amount_cents' => 'integer',
            'status' => QuoteStatus::class,
            'contracted_at' => 'datetime',
        ];
    }

    public function number(): string
    {
        return sprintf('GS-%06d', $this->id);
    }

    /**
     * @return BelongsTo<Insured, $this>
     */
    public function insured(): BelongsTo
    {
        return $this->belongsTo(Insured::class);
    }
}
