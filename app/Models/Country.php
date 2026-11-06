<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Countries\CountryData;
use App\Domain\Pricing\PricingRegion;
use Database\Factories\CountryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['code', 'name', 'region', 'subregion', 'pricing_region', 'flag_emoji'])]
class Country extends Model
{
    /** @use HasFactory<CountryFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pricing_region' => PricingRegion::class,
        ];
    }

    public function toData(): CountryData
    {
        return new CountryData(
            code: $this->code,
            name: $this->name,
            region: $this->region,
            subregion: $this->subregion,
            pricingRegion: $this->pricing_region,
            flagEmoji: (string) $this->flag_emoji,
        );
    }
}
