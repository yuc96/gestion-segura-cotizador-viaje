<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Application\QuoteDocument;
use App\Domain\Quote;

interface PdfGenerator
{
    public function render(Quote $quote): QuoteDocument;
}
