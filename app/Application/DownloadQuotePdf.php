<?php

declare(strict_types=1);

namespace App\Application;

use App\Application\Port\PdfGenerator;

final class DownloadQuotePdf
{
    public function __construct(
        private GetQuote $quotes,
        private PdfGenerator $pdfs,
    ) {}

    public function handle(int $id): QuoteDocument
    {
        return $this->pdfs->render($this->quotes->handle($id));
    }
}
