<?php

declare(strict_types=1);

namespace App\Actions\Quotes;

use App\Models\Quote;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

final class RenderQuotePdf
{
    public function download(Quote $quote): Response
    {
        $quote->loadMissing('insured');

        return Pdf::loadView('pdf.quote', ['quote' => $quote])
            ->setPaper('a4')
            ->download($quote->number().'.pdf');
    }
}
