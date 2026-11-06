<?php

declare(strict_types=1);

namespace App\Infrastructure\Pdf;

use App\Application\Port\PdfGenerator;
use App\Application\QuoteDocument;
use App\Domain\Quote;
use Barryvdh\DomPDF\Facade\Pdf;

final class DompdfQuotePdf implements PdfGenerator
{
    public function render(Quote $quote): QuoteDocument
    {
        $binary = Pdf::loadView('pdf.quote', ['quote' => $quote])
            ->setPaper('a4')
            ->output();

        return new QuoteDocument($quote->number().'.pdf', $binary);
    }
}
