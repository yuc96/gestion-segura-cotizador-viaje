<?php

declare(strict_types=1);

namespace App\Application;

use App\Application\Port\QuoteRepository;
use App\Domain\Exception\QuoteNotFound;
use App\Domain\Quote;

final class GetQuote
{
    public function __construct(private QuoteRepository $quotes) {}

    public function handle(int $id): Quote
    {
        return $this->quotes->find($id) ?? throw new QuoteNotFound('No se encontró la cotización.');
    }
}
