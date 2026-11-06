<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Application\CountryCatalogResult;
use App\Domain\Country;

interface CountryProvider
{
    public function catalog(): CountryCatalogResult;

    public function find(string $code): ?Country;
}
