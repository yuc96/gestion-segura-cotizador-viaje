<?php

declare(strict_types=1);

namespace App\Domain\Countries;

final readonly class CatalogResult
{
    /**
     * @param  list<CountryData>  $countries
     */
    public function __construct(
        public array $countries,
        public string $source,
        public ?string $notice,
    ) {}
}
