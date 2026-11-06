<?php

declare(strict_types=1);

namespace App\Application;

use App\Application\Port\CountryProvider;

final class GetCountries
{
    public function __construct(private CountryProvider $countries) {}

    public function handle(): CountryCatalogResult
    {
        return $this->countries->catalog();
    }
}
