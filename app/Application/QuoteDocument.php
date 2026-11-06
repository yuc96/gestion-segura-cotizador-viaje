<?php

declare(strict_types=1);

namespace App\Application;

final readonly class QuoteDocument
{
    public function __construct(
        public string $filename,
        public string $contents,
    ) {}
}
