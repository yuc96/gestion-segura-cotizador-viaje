<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\PricingRegion;
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
}
