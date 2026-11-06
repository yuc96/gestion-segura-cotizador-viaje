<?php

declare(strict_types=1);

namespace App\Application;

final readonly class QuoteCriteria
{
    public function __construct(
        public ?string $search,
        public ?string $status,
        public ?string $departureFrom,
        public ?string $departureTo,
        public int $page,
        public int $perPage,
    ) {}
}
