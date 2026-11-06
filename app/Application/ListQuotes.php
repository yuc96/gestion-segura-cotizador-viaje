<?php

declare(strict_types=1);

namespace App\Application;

use App\Application\Port\QuoteRepository;

final class ListQuotes
{
    public function __construct(private QuoteRepository $quotes) {}

    public function handle(QuoteCriteria $criteria): QuotePage
    {
        return $this->quotes->paginate($criteria);
    }
}
