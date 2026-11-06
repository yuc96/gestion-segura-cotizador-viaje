<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Application\QuoteCriteria;
use App\Application\QuotePage;
use App\Domain\Quote;

interface QuoteRepository
{
    public function save(Quote $quote): Quote;

    public function find(int $id): ?Quote;

    public function paginate(QuoteCriteria $criteria): QuotePage;
}
