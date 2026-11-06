<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Country;

final readonly class CountryCatalogResult
{
    /**
     * @param  list<Country>  $countries
     */
    public function __construct(
        public array $countries,
        public string $source,
        public ?string $notice,
    ) {}
}
