<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Quote;

final readonly class QuotePage
{
    /**
     * @param  list<Quote>  $items
     */
    public function __construct(
        public array $items,
        public int $currentPage,
        public int $lastPage,
        public int $perPage,
        public int $total,
    ) {}
}
