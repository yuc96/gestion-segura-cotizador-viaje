<?php

declare(strict_types=1);

namespace App\Domain;

final readonly class Country
{
    public function __construct(
        public string $code,
        public string $name,
        public string $region,
        public ?string $subregion,
        public PricingRegion $pricingRegion,
        public string $flagEmoji,
    ) {}

    /**
     * @return array{code: string, name: string, region: string, subregion: ?string, pricing_region: string, flag_emoji: string}
     */
    public function toCacheArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'region' => $this->region,
            'subregion' => $this->subregion,
            'pricing_region' => $this->pricingRegion->value,
            'flag_emoji' => $this->flagEmoji,
        ];
    }

    /**
     * @param  array{code: string, name: string, region: string, subregion?: ?string, pricing_region: string, flag_emoji?: string}  $row
     */
    public static function fromCacheArray(array $row): self
    {
        return new self(
            code: $row['code'],
            name: $row['name'],
            region: $row['region'],
            subregion: $row['subregion'] ?? null,
            pricingRegion: PricingRegion::from($row['pricing_region']),
            flagEmoji: $row['flag_emoji'] ?? '',
        );
    }
}
